<?php

namespace Modules\Tests\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\Application;
use Modules\Tests\Models\AdmissionTestCandidate;
use Modules\Tests\Models\AdmissionTestConfig;
use Modules\Tests\Models\AdmissionTestSchedule;
use Modules\Tests\Models\AdmissionTestScore;
use Modules\Tests\Services\MarksCsvImporter;
use Modules\Tests\Services\RollNumberGenerator;
use Modules\Tests\Services\TestCandidateRegistrar;

class AdmissionTestsController extends Controller
{
    public function index(): Response
    {
        $configs = AdmissionTestConfig::query()
            ->with(['program:id,code,name', 'session:id,code', 'schedule'])
            ->orderByDesc('id')
            ->get()
            ->map(fn (AdmissionTestConfig $c) => [
                'id' => $c->id,
                'program' => $c->program,
                'session' => $c->session,
                'is_test_enabled' => $c->is_test_enabled,
                'max_marks' => $c->max_marks,
                'test_weight' => $c->test_weight,
                'has_schedule' => (bool) $c->schedule,
                'schedule_date' => $c->schedule?->test_date,
                'admit_cards_published' => (bool) $c->schedule?->admit_cards_published,
                'candidate_count' => $c->schedule
                    ? AdmissionTestCandidate::where('admission_test_schedule_id', $c->schedule->id)->count()
                    : 0,
            ]);

        return Inertia::render('Admin/AdmissionTests', [
            'configs' => $configs,
            'programs' => Program::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'type']),
            'sessions' => AcademicSession::orderByDesc('is_active')->orderByDesc('id')->get(['id', 'code', 'name', 'is_active']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'academic_session_id' => ['required', 'exists:academic_sessions,id'],
        ]);

        $config = AdmissionTestConfig::firstOrCreate(
            [
                'program_id' => $data['program_id'],
                'academic_session_id' => $data['academic_session_id'],
            ],
            [
                'is_test_enabled' => false,
                'test_weight' => 0,
                'marks_weight' => 100,
            ],
        );

        return redirect()->route('admin.admission-tests.show', $config);
    }

    public function show(AdmissionTestConfig $admissionTest, TestCandidateRegistrar $registrar): Response
    {
        $admissionTest->load([
            'program:id,code,name,type',
            'session:id,code,name',
            'schedule.publisher:id,name',
        ]);

        $schedule = $admissionTest->schedule;

        // Auto-register any newly-paid applications as candidates. Idempotent.
        if ($schedule && $admissionTest->is_test_enabled) {
            $registrar->registerForSchedule($schedule);
        }

        $candidates = $schedule
            ? AdmissionTestCandidate::query()
                ->where('admission_test_schedule_id', $schedule->id)
                ->with(['application:id,application_number,student_id', 'application.student.user:id,name,email,mobile', 'score'])
                ->orderBy('roll_number')
                ->orderBy('id')
                ->get()
                ->map(fn (AdmissionTestCandidate $c) => [
                    'id' => $c->id,
                    'roll_number' => $c->roll_number,
                    'admit_card_published' => $c->admit_card_published,
                    'application_number' => $c->application?->application_number,
                    'applicant_name' => $c->application?->student?->user?->name,
                    'applicant_email' => $c->application?->student?->user?->email,
                    'raw_marks' => $c->score?->raw_marks,
                    'attendance' => $c->score?->attendance,
                    'is_locked' => (bool) $c->score?->is_locked,
                    'entered_via' => $c->score?->entered_via,
                ])
            : collect();

        return Inertia::render('Admin/AdmissionTest', [
            'config' => $admissionTest,
            'schedule' => $schedule,
            'candidates' => $candidates,
        ]);
    }

    public function update(Request $request, AdmissionTestConfig $admissionTest): RedirectResponse
    {
        $data = $request->validate([
            'is_test_enabled' => ['required', 'boolean'],
            'max_marks' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'qualifying_marks' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'test_weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'marks_weight' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'negative_marking_rule' => ['nullable', 'string', 'max:16'],
            'syllabus_url' => ['nullable', 'url', 'max:500'],
            'instructions' => ['nullable', 'string', 'max:5000'],
        ]);

        if (($data['test_weight'] ?? 0) + ($data['marks_weight'] ?? 0) > 100.01) {
            return back()->withErrors(['test_weight' => 'test_weight + marks_weight must not exceed 100.']);
        }

        $admissionTest->update($data);

        return back()->with('flash', ['success' => 'Test configuration saved.']);
    }

    public function saveSchedule(Request $request, AdmissionTestConfig $admissionTest): RedirectResponse
    {
        $data = $request->validate([
            'test_date' => ['required', 'date', 'after_or_equal:today'],
            'reporting_time' => ['nullable', 'date_format:H:i'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i', 'after:start_time'],
            'venue' => ['required', 'string', 'max:200'],
            'venue_address' => ['nullable', 'string', 'max:1000'],
        ]);

        AdmissionTestSchedule::updateOrCreate(
            ['admission_test_config_id' => $admissionTest->id],
            $data,
        );

        return back()->with('flash', ['success' => 'Schedule saved.']);
    }

    public function publishAdmitCards(
        Request $request,
        AdmissionTestConfig $admissionTest,
        RollNumberGenerator $rollGen,
        TestCandidateRegistrar $registrar,
    ): RedirectResponse {
        $schedule = $admissionTest->schedule;
        abort_unless($schedule, 422, 'No schedule defined for this test.');
        abort_unless($admissionTest->is_test_enabled, 422, 'Test is not enabled.');

        $registrar->registerForSchedule($schedule);

        $now = now();
        $candidates = AdmissionTestCandidate::where('admission_test_schedule_id', $schedule->id)
            ->orderBy('id')
            ->get();

        foreach ($candidates as $candidate) {
            $wasPublished = $candidate->admit_card_published;
            if (! $candidate->roll_number) {
                $candidate->forceFill([
                    'roll_number' => $rollGen->next($schedule),
                    'roll_assigned_at' => $now,
                ])->save();
            }
            if (! $wasPublished) {
                $candidate->forceFill([
                    'admit_card_published' => true,
                    'admit_card_published_at' => $now,
                ])->save();
                event(new \Modules\Notifications\Events\AdmitCardReleasedEvent($candidate->fresh()));
            }
        }

        $schedule->forceFill([
            'admit_cards_published' => true,
            'admit_cards_published_at' => $now,
            'admit_cards_published_by' => $request->user()->id,
        ])->save();

        return back()->with('flash', [
            'success' => "Admit cards published for {$candidates->count()} candidate(s).",
        ]);
    }

    public function saveMarks(Request $request, AdmissionTestConfig $admissionTest): RedirectResponse
    {
        $data = $request->validate([
            'rows' => ['required', 'array'],
            'rows.*.candidate_id' => ['required', 'integer'],
            'rows.*.raw_marks' => ['nullable', 'numeric', 'min:0', 'max:1000'],
            'rows.*.attendance' => ['required', 'in:present,absent'],
        ]);

        $scheduleId = $admissionTest->schedule?->id;
        abort_unless($scheduleId, 422, 'No schedule defined.');

        $candidateIds = AdmissionTestCandidate::where('admission_test_schedule_id', $scheduleId)
            ->pluck('id')->flip();

        $updated = 0;
        foreach ($data['rows'] as $row) {
            if (! isset($candidateIds[$row['candidate_id']])) {
                continue;
            }
            $score = AdmissionTestScore::firstOrNew([
                'admission_test_candidate_id' => $row['candidate_id'],
            ]);

            if ($score->is_locked) {
                continue;
            }

            $score->fill([
                'raw_marks' => $row['attendance'] === 'absent' ? null : $row['raw_marks'],
                'attendance' => $row['attendance'],
                'entered_via' => AdmissionTestScore::ENTERED_VIA_MANUAL,
                'entered_by' => $request->user()->id,
                'entered_at' => now(),
            ])->save();

            $updated++;
        }

        return back()->with('flash', ['success' => "Saved marks for {$updated} candidate(s)."]);
    }

    public function previewCsv(Request $request, AdmissionTestConfig $admissionTest, MarksCsvImporter $importer): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $result = $importer->preview($request->file('file'), $admissionTest);

        // Cache rows in session for the commit step.
        session(['csv_preview_'.$admissionTest->id => $result['rows']]);

        return back()->with('flash', [
            'csv_preview' => $result,
        ]);
    }

    public function commitCsv(Request $request, AdmissionTestConfig $admissionTest, MarksCsvImporter $importer): RedirectResponse
    {
        $rows = session('csv_preview_'.$admissionTest->id, []);
        if (empty($rows)) {
            return back()->with('flash', ['error' => 'No preview to commit. Upload a CSV first.']);
        }

        $written = $importer->commit($rows, $admissionTest, $request->user()->id);
        session()->forget('csv_preview_'.$admissionTest->id);

        return back()->with('flash', ['success' => "Committed {$written} score row(s) from CSV."]);
    }

    public function destroy(AdmissionTestConfig $admissionTest): RedirectResponse
    {
        $admissionTest->delete();

        return redirect()->route('admin.admission-tests.index')->with('flash', [
            'success' => 'Test configuration deleted.',
        ]);
    }
}
