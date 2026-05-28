<?php

namespace Modules\Academics\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Academics\Models\AcademicSubject;

class AcademicSubjectsController extends Controller
{
    public function index(Request $request): Response
    {
        $level = $request->input('level', '');
        $stream = $request->input('stream', '');
        $q = $request->input('q', '');

        $query = AcademicSubject::query()
            ->orderBy('level')->orderBy('ordering')->orderBy('name');

        if ($level !== '') {
            $query->where('level', $level);
        }
        if ($stream !== '') {
            $query->where('stream', $stream);
        }
        if ($q !== '') {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                    ->orWhere('code', 'like', "%{$q}%");
            });
        }

        return Inertia::render('Admin/AcademicSubjects', [
            'subjects' => $query->paginate(50)->withQueryString(),
            'filters' => ['level' => $level, 'stream' => $stream, 'q' => $q],
            'counts' => [
                '10th' => AcademicSubject::where('level', '10th')->count(),
                '12th' => AcademicSubject::where('level', '12th')->count(),
                'ug' => AcademicSubject::where('level', 'ug')->count(),
            ],
            'streams' => [
                AcademicSubject::STREAM_SCIENCE,
                AcademicSubject::STREAM_COMMERCE,
                AcademicSubject::STREAM_ARTS,
                AcademicSubject::STREAM_LANGUAGE,
                AcademicSubject::STREAM_COMMON,
                AcademicSubject::STREAM_VOCATIONAL,
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);

        AcademicSubject::create($data + ['is_active' => true]);

        return back()->with('flash', ['success' => 'Subject added.']);
    }

    public function update(Request $request, AcademicSubject $subject): RedirectResponse
    {
        $data = $this->validatePayload($request, $subject);

        $subject->update($data);

        return back()->with('flash', ['success' => 'Subject updated.']);
    }

    public function toggle(AcademicSubject $subject): RedirectResponse
    {
        $subject->update(['is_active' => ! $subject->is_active]);

        return back()->with('flash', [
            'success' => $subject->name.' is now '.($subject->is_active ? 'enabled' : 'disabled').'.',
        ]);
    }

    public function destroy(AcademicSubject $subject): RedirectResponse
    {
        $subject->delete();

        return back()->with('flash', ['success' => 'Subject deleted.']);
    }

    protected function validatePayload(Request $request, ?AcademicSubject $subject = null): array
    {
        $codeLevelUnique = Rule::unique('academic_subjects', 'code')
            ->where('level', $request->input('level'))
            ->ignore($subject?->id);

        return $request->validate([
            'code' => ['required', 'string', 'max:32', $codeLevelUnique],
            'name' => ['required', 'string', 'max:120'],
            'level' => ['required', Rule::in(['10th', '12th', 'ug'])],
            'stream' => ['nullable', 'string', 'max:24'],
            'is_language' => ['sometimes', 'boolean'],
            'ordering' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }
}
