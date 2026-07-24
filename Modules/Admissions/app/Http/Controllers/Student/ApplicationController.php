<?php

namespace Modules\Admissions\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Academics\Models\Program;
use Modules\Academics\Models\ProgrammeCoursePool;
use Modules\Admissions\Actions\ApplicationSubmitAction;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\ApplicationCourseSelection;
use Modules\Admissions\Models\WithdrawalRequest;
use Modules\Admissions\Pdf\ApplicationReceiptPdf;
use Modules\Students\Models\Student;
use Modules\Students\Services\ProfileCompletionService;

class ApplicationController extends Controller
{
    public function __construct(protected ProfileCompletionService $completion) {}

    public function index(Request $request): Response
    {
        $student = Student::firstOrCreate(['user_id' => $request->user()->id]);

        return Inertia::render('Student/Applications', [
            'applications' => $student->applications()
                ->with(['program:id,code,name,type', 'session:id,code'])
                ->orderByDesc('id')
                ->get(),
            'programmes' => Program::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'type', 'intake_capacity']),
            'active_session' => AcademicSession::where('is_active', true)->first(),
            'profile_locked' => (bool) $student->profile_locked,
        ]);
    }

    public function start(Request $request): RedirectResponse
    {
        $student = Student::firstOrCreate(['user_id' => $request->user()->id]);

        if (! $student->profile_locked) {
            return back()->with('flash', [
                'error' => 'Please complete and submit your profile before starting an application.',
            ]);
        }

        $data = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
        ]);

        $session = AcademicSession::where('is_active', true)->firstOrFail();

        $application = Application::firstOrCreate(
            [
                'student_id' => $student->id,
                'program_id' => $data['program_id'],
                'academic_session_id' => $session->id,
            ],
            [
                'status' => Application::STATUS_DRAFT,
                'draft_data' => [],
            ],
        );

        return redirect()->route('student.applications.edit', $application);
    }

    public function edit(Request $request, Application $application)
    {
        $this->authorizeOwnership($application, $request);

        $student = $application->student;
        if (! $student->profile_locked) {
            return redirect()->route('student.applications.index')->with('flash', [
                'error' => 'Please complete and submit your profile before opening any application.',
            ]);
        }

        $application->load([
            'program:id,code,name,type,department_id',
            'program.department:id,name',
            'session:id,code',
            'courseSelections.pool',
        ]);

        $pools = ProgrammeCoursePool::where('program_id', $application->program_id)
            ->where('is_active', true)
            ->orderBy('category')->orderBy('ordering')->orderBy('course_name')
            ->get()
            ->groupBy('category');

        $selectionsByCategory = $application->courseSelections
            ->groupBy('category')
            ->map(fn ($rows) => $rows->pluck('pool_id')->all());

        // Pick the human-readable picks (category → [course rows]) for the
        // post-submit acknowledgement view.
        $picksByCategory = $application->courseSelections
            ->groupBy('category')
            ->map(fn ($rows) => $rows->map(fn ($sel) => [
                'code' => $sel->pool?->course_code,
                'name' => $sel->pool?->course_name,
                'credits' => $sel->pool?->credits,
            ])->values());

        $student = $application->student()
            ->with('user:id,name,email,mobile', 'category:id,code,name')
            ->first();

        $academicRecords = $student->academicRecords()->orderBy('level')->get();

        $latestWithdrawal = WithdrawalRequest::where('application_id', $application->id)
            ->latest('id')->first();

        return Inertia::render('Student/Application', [
            'application' => $application,
            'student' => $student,
            'academic_records' => $academicRecords,
            'pools' => $pools,
            'selections' => $selectionsByCategory,
            'picks' => $picksByCategory,
            'categories' => ProgrammeCoursePool::CATEGORIES,
            'latest_withdrawal' => $latestWithdrawal,
        ]);
    }

    public function saveDraft(Request $request, Application $application): JsonResponse
    {
        $this->authorizeOwnership($application, $request);

        if (! $application->student->profile_locked) {
            return response()->json(['ok' => false, 'error' => 'Profile not locked.'], 403);
        }

        if ($application->status !== Application::STATUS_DRAFT) {
            return response()->json(['ok' => false, 'error' => 'Application already submitted.'], 422);
        }

        $data = $request->validate([
            'selections' => ['nullable', 'array'],
            'selections.*' => ['array'],
            'selections.*.*' => ['integer', 'exists:programme_course_pools,id'],
            'special_request' => ['nullable', 'string', 'max:1000'],
            'declaration_anti_ragging' => ['sometimes', 'boolean'],
            'declaration_information_true' => ['sometimes', 'boolean'],
        ]);

        $this->syncSelections($application, $data['selections'] ?? []);

        $application->forceFill([
            'special_request' => $data['special_request'] ?? $application->special_request,
            'declaration_anti_ragging' => $data['declaration_anti_ragging'] ?? $application->declaration_anti_ragging,
            'declaration_information_true' => $data['declaration_information_true'] ?? $application->declaration_information_true,
        ])->save();

        return response()->json(['ok' => true, 'saved_at' => now()->toIso8601String()]);
    }

    public function download(Request $request, Application $application, ApplicationReceiptPdf $pdf): \Illuminate\Http\Response|RedirectResponse
    {
        $this->authorizeOwnership($application, $request);

        if ($application->status === Application::STATUS_DRAFT) {
            return back()->with('flash', ['error' => 'PDF is available only after the application is submitted.']);
        }

        return $pdf($application);
    }

    public function submit(Request $request, Application $application, ApplicationSubmitAction $action): RedirectResponse
    {
        $this->authorizeOwnership($application, $request);

        abort_unless($application->student->profile_locked, 403, 'Profile not locked.');

        // NEP 2020 combination is captured as a PREFERENCE at admission — the
        // college finalises the credit-compliant, semester-wise plan afterwards.
        // Required: one Minor discipline + at least one AEC language. The rest
        // (SEC / VAC / MDC / internship / research) are optional preferences.
        $data = $request->validate([
            'selections' => ['required', 'array'],
            'selections.minor' => ['required', 'array', 'size:1'],
            'selections.minor.*' => ['integer', 'exists:programme_course_pools,id'],
            'selections.aec' => ['required', 'array', 'min:1'],
            'selections.aec.*' => ['integer', 'exists:programme_course_pools,id'],
            'selections.sec' => ['sometimes', 'array'],
            'selections.sec.*' => ['integer', 'exists:programme_course_pools,id'],
            'selections.vac' => ['sometimes', 'array'],
            'selections.vac.*' => ['integer', 'exists:programme_course_pools,id'],
            'selections.mdc' => ['sometimes', 'array'],
            'selections.mdc.*' => ['integer', 'exists:programme_course_pools,id'],
            'selections.internship' => ['sometimes', 'array'],
            'selections.internship.*' => ['integer', 'exists:programme_course_pools,id'],
            'selections.research' => ['sometimes', 'array'],
            'selections.research.*' => ['integer', 'exists:programme_course_pools,id'],
            'special_request' => ['nullable', 'string', 'max:1000'],
            'declaration_anti_ragging' => ['accepted'],
            'declaration_information_true' => ['accepted'],
        ], [
            'selections.minor.size' => 'Choose exactly one Minor discipline.',
            'selections.aec.min' => 'Choose at least one Ability Enhancement (language) course.',
        ]);

        $this->syncSelections($application, $data['selections']);

        $application->forceFill([
            'special_request' => $data['special_request'] ?? null,
            'declaration_anti_ragging' => true,
            'declaration_information_true' => true,
        ])->save();

        $action->execute($application);

        return redirect()->route('student.payments.show', $application)->with('flash', [
            'success' => "Application {$application->fresh()->application_number} submitted. Please pay the application fee to complete.",
        ]);
    }

    protected function syncSelections(Application $application, array $selections): void
    {
        $rows = [];
        foreach ($selections as $category => $poolIds) {
            foreach ((array) $poolIds as $poolId) {
                $rows[] = [
                    'application_id' => $application->id,
                    'pool_id' => (int) $poolId,
                    'category' => $category,
                ];
            }
        }

        ApplicationCourseSelection::where('application_id', $application->id)->delete();
        if (! empty($rows)) {
            ApplicationCourseSelection::insert(array_map(
                fn ($r) => $r + ['created_at' => now(), 'updated_at' => now()],
                $rows,
            ));
        }
    }

    protected function authorizeOwnership(Application $application, Request $request): void
    {
        abort_unless(
            $application->student?->user_id === $request->user()->id,
            403,
            'Not your application.',
        );
    }
}
