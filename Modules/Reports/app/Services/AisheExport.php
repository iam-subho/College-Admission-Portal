<?php

namespace Modules\Reports\Services;

use Illuminate\Http\Response;
use League\Csv\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * AISHE (All India Survey on Higher Education) export.
 *
 * Builds the student-enrolment file in the AISHE Module II shape — one row
 * per admitted student with the columns required by the AISHE portal.
 *
 * Real AISHE submission also needs Module I (Institute) and Module III
 * (Faculty) which are filled by the registrar manually — we cover only
 * Module II (Students) here since that is the row-per-student data we own.
 */
class AisheExport
{
    /**
     * Columns chosen per AISHE Module II template. Order is significant;
     * the AISHE portal validates by column position.
     */
    public const COLUMNS = [
        'AISHE Student ID', 'Aadhaar (last 4)', 'Name', 'Father Name', 'Mother Name',
        'Date of Birth', 'Gender', 'Category', 'Religion', 'Domicile State',
        'Nationality', 'Disability (Y/N)', 'Annual Family Income',
        'Programme Code', 'Programme Name', 'Programme Type', 'Year of Enrolment',
        'Session', 'Admission Number', 'Mode of Admission',
    ];

    public function stream(?string $sessionCode = null): StreamedResponse
    {
        $filename = 'aishe_module_ii_'.($sessionCode ?? 'all').'_'.now()->format('Y-m-d').'.csv';

        return new StreamedResponse(function () use ($sessionCode) {
            $csv = Writer::createFromStream(fopen('php://output', 'w'));
            $csv->insertOne(self::COLUMNS);

            \Modules\Seats\Models\SeatAllocation::query()
                ->with([
                    'application.student.user',
                    'application.student.category',
                    'application.program',
                    'application.session',
                ])
                ->where('status', \Modules\Seats\Models\SeatAllocation::STATUS_ADMITTED)
                ->when($sessionCode, function ($q) use ($sessionCode) {
                    $q->whereHas('application.session', fn ($s) => $s->where('code', $sessionCode));
                })
                ->orderBy('admitted_at')
                ->chunk(200, function ($chunk) use ($csv) {
                    foreach ($chunk as $alloc) {
                        $csv->insertOne($this->row($alloc));
                    }
                });
        }, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.addslashes($filename).'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    protected function row(\Modules\Seats\Models\SeatAllocation $alloc): array
    {
        $app = $alloc->application;
        $student = $app?->student;
        $user = $student?->user;
        $program = $app?->program;
        $session = $app?->session;

        return [
            sprintf('AISHE-%06d', $alloc->id),
            $student?->aadhaar_last4 ?? '',
            $user?->name ?? $student?->aadhaar_full_name ?? '',
            $student?->father_name ?? '',
            $student?->mother_name ?? '',
            $student?->dob ? \Carbon\Carbon::parse($student->dob)->format('d/m/Y') : '',
            $student?->gender ?? '',
            $student?->category?->code ?? 'UR',
            $student?->religion ?? '',
            $student?->domicile_state ?? '',
            $student?->nationality ?? 'Indian',
            ! empty($student?->is_pwd) ? 'Y' : 'N',
            $student?->annual_family_income ?? '',
            $program?->code ?? '',
            $program?->name ?? '',
            $program?->type ?? '',
            $session?->commencement_date ? \Carbon\Carbon::parse($session->commencement_date)->format('Y') : '',
            $session?->code ?? '',
            $app?->application_number ?? '',
            $alloc->source === 'spot' ? 'Spot' : 'Merit',
        ];
    }
}
