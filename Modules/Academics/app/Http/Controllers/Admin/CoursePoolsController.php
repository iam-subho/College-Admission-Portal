<?php

namespace Modules\Academics\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Academics\Models\Program;
use Modules\Academics\Models\ProgrammeCoursePool;

class CoursePoolsController extends Controller
{
    public function index(Request $request): Response
    {
        $programId = $request->input('program_id') ?: Program::where('is_active', true)->orderBy('name')->value('id');

        return Inertia::render('Admin/CoursePools', [
            'programmes' => Program::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name', 'type']),
            'selected_program_id' => $programId,
            'categories' => ProgrammeCoursePool::CATEGORIES,
            'pools' => $programId
                ? ProgrammeCoursePool::where('program_id', $programId)
                    ->orderBy('category')->orderBy('ordering')->orderBy('course_name')
                    ->get()
                : collect(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'program_id' => ['required', 'exists:programs,id'],
            'category' => ['required', 'in:'.implode(',', array_keys(ProgrammeCoursePool::CATEGORIES))],
            'course_code' => ['nullable', 'string', 'max:30'],
            'course_name' => ['required', 'string', 'max:200'],
            'credits' => ['nullable', 'integer', 'min:0', 'max:20'],
            'is_default' => ['sometimes', 'boolean'],
            'ordering' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        ProgrammeCoursePool::create($data + ['is_active' => true]);

        return back()->with('flash', ['success' => 'Course added to pool.']);
    }

    public function update(Request $request, ProgrammeCoursePool $pool): RedirectResponse
    {
        $data = $request->validate([
            'category' => ['sometimes', 'in:'.implode(',', array_keys(ProgrammeCoursePool::CATEGORIES))],
            'course_code' => ['nullable', 'string', 'max:30'],
            'course_name' => ['sometimes', 'string', 'max:200'],
            'credits' => ['nullable', 'integer', 'min:0', 'max:20'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'ordering' => ['nullable', 'integer'],
            'description' => ['nullable', 'string', 'max:500'],
        ]);

        $pool->update($data);

        return back()->with('flash', ['success' => 'Course updated.']);
    }

    public function destroy(ProgrammeCoursePool $pool): RedirectResponse
    {
        $pool->delete();

        return back()->with('flash', ['success' => 'Course removed.']);
    }
}
