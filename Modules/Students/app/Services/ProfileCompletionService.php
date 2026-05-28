<?php

namespace Modules\Students\Services;

use Modules\Documents\Models\DocumentType;
use Modules\Documents\Models\UploadedDocument;
use Modules\Students\Models\Student;
use Modules\Students\Models\StudentAcademicRecord;

class ProfileCompletionService
{
    /** Each section maps required attribute keys → human label. */
    public const PERSONAL_REQUIRED = [
        'aadhaar_full_name' => 'Full Name (as per Aadhaar)',
        'gender' => 'Gender',
        'dob' => 'Date of Birth',
        'nationality' => 'Nationality',
        'aadhaar_last4' => 'Aadhaar Number',
        'reservation_category_id' => 'Reservation Category',
        'religion' => 'Religion',
        'mother_tongue' => 'Mother Tongue',
    ];

    public const FAMILY_REQUIRED = [
        'father_name' => "Father's Name",
        'father_occupation' => "Father's Occupation",
        'father_mobile' => "Father's Mobile",
        'mother_name' => "Mother's Name",
        'annual_family_income' => 'Total Annual Family Income',
        'emergency_contact' => 'Emergency Contact',
    ];

    public const ADDRESS_REQUIRED = [
        'house_no' => 'House / Building / Street',
        'locality' => 'Locality / Area',
        'state' => 'State / UT',
        'district' => 'District',
        'pincode' => 'Pincode',
        'domicile_state' => 'Domicile State',
    ];

    public const ADDRESS_CORRESPONDENCE_REQUIRED = [
        'correspondence_house_no' => 'Correspondence House / Building / Street',
        'correspondence_locality' => 'Correspondence Locality',
        'correspondence_state' => 'Correspondence State',
        'correspondence_district' => 'Correspondence District',
        'correspondence_pincode' => 'Correspondence Pincode',
    ];

    public const OTHER_DEFAULTS = [
        'ncc_certificate' => 'NCC Certificate',
        'sports_level' => 'Sports Achievement Level',
        'accommodation' => 'Hosteller / Day Scholar',
        'communication_preference' => 'Communication Preference',
    ];

    /**
     * Compute per-section status for one student.
     *
     * @return array{
     *   personal: bool, family: bool, address: bool, academic: bool, other: bool, uploads: bool,
     *   all_complete: bool, percent: int, locked: bool,
     *   missing: array<string, array<int, string>>
     * }
     */
    public function status(Student $student): array
    {
        $missing = [
            'personal' => $this->missingFor($student, self::PERSONAL_REQUIRED),
            'family' => $this->missingFor($student, self::FAMILY_REQUIRED),
            'address' => $this->missingForAddress($student),
            'academic' => $this->missingForAcademic($student),
            'other' => $this->missingForOther($student),
            'uploads' => $this->missingForUploads($student),
        ];

        $sections = [
            'personal' => empty($missing['personal']),
            'family' => empty($missing['family']),
            'address' => empty($missing['address']),
            'academic' => empty($missing['academic']),
            'other' => empty($missing['other']),
            'uploads' => empty($missing['uploads']),
        ];

        $done = count(array_filter($sections));
        $total = count($sections);

        return [
            ...$sections,
            'all_complete' => $done === $total,
            'percent' => (int) round(($done / $total) * 100),
            'locked' => (bool) $student->profile_locked,
            'missing' => $missing,
        ];
    }

    public function canLock(Student $student): bool
    {
        $status = $this->status($student);

        return $status['all_complete'] && ! $status['locked'];
    }

    /**
     * @param  array<string, string>  $keyLabelMap
     * @return string[]  Human labels of the fields still missing.
     */
    protected function missingFor(Student $s, array $keyLabelMap): array
    {
        $out = [];
        foreach ($keyLabelMap as $key => $label) {
            if (blank($s->{$key})) {
                $out[] = $label;
            }
        }

        return $out;
    }

    /** @return string[] */
    protected function missingForAddress(Student $s): array
    {
        $missing = $this->missingFor($s, self::ADDRESS_REQUIRED);

        if (! $s->correspondence_same_as_permanent) {
            $missing = array_merge($missing, $this->missingFor($s, self::ADDRESS_CORRESPONDENCE_REQUIRED));
        }

        return $missing;
    }

    /** @return string[] */
    protected function missingForAcademic(Student $s): array
    {
        $records = $s->academicRecords()->get()->keyBy('level');
        $missing = [];

        foreach ([StudentAcademicRecord::LEVEL_10TH => 'Class X record', StudentAcademicRecord::LEVEL_12TH => 'Class XII record'] as $level => $label) {
            $r = $records->get($level);
            if (! $r || blank($r->board) || blank($r->passing_year) || blank($r->school_name) || blank($r->percentage)) {
                $missing[] = $label;
            }
        }

        return $missing;
    }

    /** @return string[] */
    protected function missingForOther(Student $s): array
    {
        return $this->missingFor($s, self::OTHER_DEFAULTS);
    }

    /** @return string[] */
    protected function missingForUploads(Student $s): array
    {
        $required = DocumentType::where('is_active', true)
            ->where('required_by_default', true)
            ->get(['id', 'code', 'label']);

        if ($required->isEmpty()) {
            return [];
        }

        $uploaded = UploadedDocument::where('student_id', $s->id)
            ->whereIn('document_type_id', $required->pluck('id'))
            ->whereIn('status', ['pending', 'approved'])
            ->pluck('document_type_id')
            ->all();

        return $required->reject(fn ($t) => in_array($t->id, $uploaded, true))
            ->pluck('label')
            ->all();
    }

    /** Lock the profile. Caller must check canLock() first. */
    public function lock(Student $student): void
    {
        $student->forceFill([
            'profile_locked' => true,
            'profile_completed_at' => $student->profile_completed_at ?? now(),
            'profile_completion' => 100,
        ])->save();
    }
}
