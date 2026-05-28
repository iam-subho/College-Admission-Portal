<?php

namespace Modules\Students\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Academics\Models\AcademicSubject;
use Modules\Students\Models\Student;
use Modules\Students\Models\StudentAcademicRecord;
use Modules\Students\Models\StudentEntranceExam;

class AcademicRecordsController extends Controller
{
    public function index(Request $request): Response
    {
        $student = Student::firstOrCreate(['user_id' => $request->user()->id]);

        $subjects = AcademicSubject::query()
            ->active()
            ->orderBy('level')->orderBy('stream')->orderBy('ordering')->orderBy('name')
            ->get(['id', 'code', 'name', 'level', 'stream'])
            ->groupBy('level')
            ->map(fn ($rows) => $rows->map(fn ($s) => [
                'value' => $s->code,
                'label' => $s->name,
                'group' => $s->stream ?? 'Other',
                'sub' => $s->code,
            ])->values()->all());

        return Inertia::render('Student/AcademicRecords', [
            'records' => $student->academicRecords()->orderBy('level')->get(),
            'entrance_exams' => $student->entranceExams()->orderByDesc('exam_year')->get(),
            'levels' => [
                StudentAcademicRecord::LEVEL_10TH,
                StudentAcademicRecord::LEVEL_12TH,
                StudentAcademicRecord::LEVEL_UG,
            ],
            'subject_options' => $subjects,
        ]);
    }

    public function upsert(Request $request): RedirectResponse
    {
        $this->abortIfLocked($request);

        $data = $request->validate([
            'level' => ['required', 'in:10th,12th,ug'],
            'board' => ['required', 'string', 'max:120'],
            'passing_year' => ['required', 'integer', 'min:1990', 'max:2050'],
            'school_name' => ['required', 'string', 'max:200'],
            'school_code' => ['nullable', 'string', 'max:30'],
            'roll_number' => ['nullable', 'string', 'max:60'],
            'stream' => ['nullable', 'string', 'max:40'],
            'medium' => ['nullable', 'string', 'max:30'],
            'cgpa' => ['nullable', 'numeric', 'min:0', 'max:10'],
            'full_marks' => ['nullable', 'numeric', 'min:1'],
            'obtained_marks' => ['nullable', 'numeric', 'min:0'],
            'aggregate_best5_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'subjects' => ['nullable', 'array'],
            'subjects.*.name' => ['required_with:subjects', 'string', 'max:120'],
            'subjects.*.code' => ['nullable', 'string', 'max:32'],
            'subjects.*.theory' => ['nullable', 'numeric', 'min:0'],
            'subjects.*.practical' => ['nullable', 'numeric', 'min:0'],
            'subjects.*.full_marks' => ['required_with:subjects', 'numeric', 'min:1'],
        ]);

        $student = Student::firstOrCreate(['user_id' => $request->user()->id]);

        $payload = [
            'board' => $data['board'],
            'passing_year' => $data['passing_year'],
            'school_name' => $data['school_name'],
            'school_code' => $data['school_code'] ?? null,
            'roll_number' => $data['roll_number'] ?? null,
            'medium' => $data['medium'] ?? null,
            'cgpa' => $data['cgpa'] ?? null,
        ];

        if ($data['level'] === StudentAcademicRecord::LEVEL_10TH) {
            [$fm, $om, $pct] = $this->computeFor10th($data);
            $payload['stream'] = null;
            $payload['subjects'] = null;
            $payload['aggregate_best5_pct'] = null;
        } else {
            $subjects = $this->normaliseSubjects($data['subjects'] ?? []);
            [$fm, $om, $pct] = $this->computeFromSubjects($subjects);
            $payload['stream'] = $data['stream'] ?? null;
            $payload['subjects'] = $subjects;
            $payload['aggregate_best5_pct'] = $data['aggregate_best5_pct'] ?? null;
        }

        $payload['full_marks'] = $fm;
        $payload['obtained_marks'] = $om;
        $payload['percentage'] = $pct;

        StudentAcademicRecord::updateOrCreate(
            ['student_id' => $student->id, 'level' => $data['level']],
            $payload,
        );

        return back()->with('flash', ['success' => 'Academic record saved.']);
    }

    public function storeEntranceExam(Request $request): RedirectResponse
    {
        $this->abortIfLocked($request);

        $data = $request->validate([
            'exam_name' => ['required', 'string', 'max:60'],
            'roll_number' => ['nullable', 'string', 'max:60'],
            'score' => ['nullable', 'string', 'max:40'],
            'exam_year' => ['nullable', 'integer', 'min:2000', 'max:2099'],
        ]);

        $student = Student::firstOrCreate(['user_id' => $request->user()->id]);

        StudentEntranceExam::create($data + ['student_id' => $student->id]);

        return back()->with('flash', ['success' => 'Entrance exam added.']);
    }

    public function destroyEntranceExam(Request $request, int $id): RedirectResponse
    {
        $this->abortIfLocked($request);

        $student = Student::firstOrCreate(['user_id' => $request->user()->id]);
        StudentEntranceExam::where('student_id', $student->id)->where('id', $id)->delete();

        return back()->with('flash', ['success' => 'Entrance exam removed.']);
    }

    protected function abortIfLocked(Request $request): void
    {
        $student = Student::firstOrCreate(['user_id' => $request->user()->id]);
        abort_if($student->profile_locked, 403, 'Profile locked. Contact admissions for changes.');
    }

    /**
     * @return array{0: float|null, 1: float|null, 2: float|null}
     */
    protected function computeFor10th(array $data): array
    {
        $fm = (float) ($data['full_marks'] ?? 0);
        $om = (float) ($data['obtained_marks'] ?? 0);
        $pct = $fm > 0 ? round(($om / $fm) * 100, 2) : null;

        return [$fm > 0 ? $fm : null, $fm > 0 ? $om : null, $pct];
    }

    /**
     * @param  array<int, array<string, mixed>>  $subjects
     * @return array<int, array{name: string, code: ?string, theory: float, practical: float, obtained_marks: float, full_marks: float, percentage: ?float}>
     */
    protected function normaliseSubjects(array $subjects): array
    {
        return collect($subjects)->map(function (array $s) {
            $theory = (float) ($s['theory'] ?? 0);
            $practical = (float) ($s['practical'] ?? 0);
            $obtained = $theory + $practical;
            $full = (float) ($s['full_marks'] ?? 0);

            return [
                'name' => (string) $s['name'],
                'code' => $s['code'] ?? null,
                'theory' => $theory,
                'practical' => $practical,
                'obtained_marks' => $obtained,
                'full_marks' => $full,
                'percentage' => $full > 0 ? round(($obtained / $full) * 100, 2) : null,
            ];
        })->values()->all();
    }

    /**
     * @return array{0: float|null, 1: float|null, 2: float|null}
     */
    protected function computeFromSubjects(array $subjects): array
    {
        $fm = 0.0;
        $om = 0.0;
        foreach ($subjects as $s) {
            $fm += (float) ($s['full_marks'] ?? 0);
            $om += (float) ($s['obtained_marks'] ?? 0);
        }
        $pct = $fm > 0 ? round(($om / $fm) * 100, 2) : null;

        return [$fm > 0 ? $fm : null, $fm > 0 ? $om : null, $pct];
    }
}
