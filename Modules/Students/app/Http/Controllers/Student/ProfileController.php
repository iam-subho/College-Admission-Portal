<?php

namespace Modules\Students\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Models\ReservationCategory;
use Modules\Students\Models\Student;
use Modules\Students\Services\ProfileCompletionService;

class ProfileController extends Controller
{
    public function __construct(protected ProfileCompletionService $completion)
    {
    }

    /** Default landing — sends user to Personal Details. */
    public function show(): RedirectResponse
    {
        return redirect()->route('student.profile.personal');
    }

    // ---------- PERSONAL ----------

    public function showPersonal(Request $request): Response
    {
        return Inertia::render('Student/PersonalDetails', [
            'student' => $this->student($request)->fresh(),
            'categories' => $this->categories(),
        ]);
    }

    public function updatePersonal(Request $request): RedirectResponse
    {
        $this->abortIfLocked($request);

        $data = $request->validate([
            'aadhaar_full_name' => ['nullable', 'string', 'max:200'],
            'abc_id' => ['nullable', 'string', 'max:32'],
            'gender' => ['nullable', 'in:Male,Female,Other,Prefer not to say'],
            'dob' => ['nullable', 'date', 'before:today'],
            'nationality' => ['nullable', 'string', 'max:32'],
            'foreign_national' => ['sometimes', 'boolean'],
            'aadhaar' => ['nullable', 'digits:12'],
            'reservation_category_id' => ['nullable', 'exists:reservation_categories,id'],
            'sub_caste' => ['nullable', 'string', 'max:60'],
            'religion' => ['nullable', 'string', 'max:32'],
            'is_minority' => ['sometimes', 'boolean'],
            'mother_tongue' => ['nullable', 'string', 'max:32'],
            'blood_group' => ['nullable', 'string', 'max:6'],
            'category_certificate_no' => ['nullable', 'string', 'max:60'],
            'category_cert_issuer' => ['nullable', 'string', 'max:120'],
            'category_cert_date' => ['nullable', 'date'],
            'category_cert_validity_year' => ['nullable', 'integer', 'min:2000', 'max:2099'],
            'income_certificate_no' => ['nullable', 'string', 'max:60'],
            'pwd_type' => ['nullable', 'string', 'max:60'],
            'pwd_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'udid_number' => ['nullable', 'string', 'max:32'],
        ]);

        $student = $this->student($request);

        if (! empty($data['aadhaar'])) {
            $student->aadhaar_encrypted = encrypt($data['aadhaar']);
            $student->aadhaar_last4 = substr($data['aadhaar'], -4);
        }
        unset($data['aadhaar']);

        $student->fill($data);
        $this->refreshCompletion($student);
        $student->save();

        return back()->with('flash', ['success' => 'Personal details saved.']);
    }

    // ---------- FAMILY ----------

    public function showFamily(Request $request): Response
    {
        return Inertia::render('Student/FamilyDetails', [
            'student' => $this->student($request)->fresh(),
        ]);
    }

    public function updateFamily(Request $request): RedirectResponse
    {
        $this->abortIfLocked($request);

        $data = $request->validate([
            'father_name' => ['nullable', 'string', 'max:120'],
            'father_occupation' => ['nullable', 'string', 'max:80'],
            'father_qualification' => ['nullable', 'string', 'max:80'],
            'father_income' => ['nullable', 'numeric', 'min:0'],
            'father_mobile' => ['nullable', 'regex:/^[6-9]\d{9}$/'],
            'father_email' => ['nullable', 'email', 'max:120'],

            'mother_name' => ['nullable', 'string', 'max:120'],
            'mother_occupation' => ['nullable', 'string', 'max:80'],
            'mother_qualification' => ['nullable', 'string', 'max:80'],
            'mother_income' => ['nullable', 'numeric', 'min:0'],
            'mother_mobile' => ['nullable', 'regex:/^[6-9]\d{9}$/'],
            'mother_email' => ['nullable', 'email', 'max:120'],

            'guardian_mobile' => ['nullable', 'regex:/^[6-9]\d{9}$/'],
            'annual_family_income' => ['nullable', 'numeric', 'min:0'],
            'siblings_count' => ['nullable', 'integer', 'min:0', 'max:20'],
            'family_in_govt_service' => ['sometimes', 'boolean'],
            'is_single_parent' => ['sometimes', 'boolean'],
            'is_first_generation_graduate' => ['sometimes', 'boolean'],
            'emergency_contact' => ['nullable', 'regex:/^[6-9]\d{9}$/'],
        ]);

        $student = $this->student($request);
        $student->fill($data);
        $this->refreshCompletion($student);
        $student->save();

        return back()->with('flash', ['success' => 'Family details saved.']);
    }

    // ---------- ADDRESS ----------

    public function showAddress(Request $request): Response
    {
        return Inertia::render('Student/AddressContact', [
            'student' => $this->student($request)->fresh()->load('user:id,name,email,mobile'),
        ]);
    }

    public function updateAddress(Request $request): RedirectResponse
    {
        $this->abortIfLocked($request);

        $data = $request->validate([
            'address' => ['nullable', 'string', 'max:500'],
            'house_no' => ['nullable', 'string', 'max:200'],
            'locality' => ['nullable', 'string', 'max:120'],
            'country' => ['nullable', 'string', 'max:60'],
            'state' => ['nullable', 'string', 'max:40'],
            'district' => ['nullable', 'string', 'max:60'],
            'taluka' => ['nullable', 'string', 'max:60'],
            'pincode' => ['nullable', 'regex:/^\d{6}$/'],
            'domicile_state' => ['nullable', 'string', 'max:40'],
            'domicile_district' => ['nullable', 'string', 'max:60'],

            'correspondence_same_as_permanent' => ['sometimes', 'boolean'],
            'correspondence_house_no' => ['nullable', 'string', 'max:200'],
            'correspondence_locality' => ['nullable', 'string', 'max:120'],
            'correspondence_taluka' => ['nullable', 'string', 'max:60'],
            'correspondence_district' => ['nullable', 'string', 'max:60'],
            'correspondence_state' => ['nullable', 'string', 'max:40'],
            'correspondence_country' => ['nullable', 'string', 'max:60'],
            'correspondence_pincode' => ['nullable', 'regex:/^\d{6}$/'],
        ]);

        $student = $this->student($request);

        if (! empty($data['correspondence_same_as_permanent'])) {
            foreach ([
                'correspondence_house_no' => $data['house_no'] ?? null,
                'correspondence_locality' => $data['locality'] ?? null,
                'correspondence_taluka' => $data['taluka'] ?? null,
                'correspondence_district' => $data['district'] ?? null,
                'correspondence_state' => $data['state'] ?? null,
                'correspondence_country' => $data['country'] ?? null,
                'correspondence_pincode' => $data['pincode'] ?? null,
            ] as $key => $value) {
                $data[$key] = $value;
            }
        }

        $student->fill($data);
        $this->refreshCompletion($student);
        $student->save();

        return back()->with('flash', ['success' => 'Address saved.']);
    }

    // ---------- OTHER DETAILS ----------

    public function showOther(Request $request): Response
    {
        return Inertia::render('Student/OtherDetails', [
            'student' => $this->student($request)->fresh(),
        ]);
    }

    public function updateOther(Request $request): RedirectResponse
    {
        $this->abortIfLocked($request);

        $data = $request->validate([
            'ncc_certificate' => ['nullable', 'in:None,A,B,C'],
            'is_nss_volunteer' => ['sometimes', 'boolean'],
            'sports_level' => ['nullable', 'in:None,School,District,State,National,International'],
            'awards' => ['nullable', 'string', 'max:2000'],
            'accommodation' => ['nullable', 'in:day_scholar,hosteller'],
            'transport_required' => ['sometimes', 'boolean'],
            'communication_preference' => ['nullable', 'in:email_sms,email,sms,whatsapp'],
        ]);

        $student = $this->student($request);
        $student->fill($data);
        $this->refreshCompletion($student);
        $student->save();

        return back()->with('flash', ['success' => 'Preferences saved.']);
    }

    // ---------- REVIEW & FINAL SUBMIT ----------

    public function showReview(Request $request): Response
    {
        $student = $this->student($request)->fresh()->load('user:id,name,email,mobile', 'category:id,code,name');
        $status = $this->completion->status($student);

        return Inertia::render('Student/ProfileReview', [
            'student' => $student,
            'status' => $status,
            'academic_records' => $student->academicRecords()->orderBy('level')->get(),
        ]);
    }

    public function finalSubmit(Request $request): RedirectResponse
    {
        $student = $this->student($request);

        if (! $this->completion->canLock($student)) {
            return back()->with('flash', [
                'error' => 'Profile is not yet complete or is already locked. Please complete every section before submitting.',
            ]);
        }

        $request->validate([
            'declaration_information_true' => ['accepted'],
        ]);

        $this->completion->lock($student);

        app(\Modules\Audit\Services\DpdpConsentRecorder::class)->record(
            scope: \Modules\Users\Models\DpdpConsent::SCOPE_PROFILE_LOCK,
            userId: $student->user_id,
            request: $request,
            metadata: ['student_id' => $student->id],
        );

        return back()->with('flash', [
            'success' => 'Profile submitted and locked. You can now apply for programmes.',
        ]);
    }

    // ---------- helpers ----------

    protected function abortIfLocked(Request $request): void
    {
        $student = $this->student($request);
        abort_if(
            $student->profile_locked,
            403,
            'Your profile has been submitted and locked. Contact admissions for changes.',
        );
    }

    protected function student(Request $request): Student
    {
        return Student::firstOrCreate(['user_id' => $request->user()->id]);
    }

    protected function categories()
    {
        return ReservationCategory::where('is_active', true)
            ->where('is_horizontal', false)
            ->orderBy('ordering')
            ->get(['id', 'code', 'name']);
    }

    protected function refreshCompletion(Student $s): void
    {
        $s->profile_completion = $this->completion->status($s)['percent'];
    }
}
