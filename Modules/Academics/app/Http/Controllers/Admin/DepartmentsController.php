<?php

namespace Modules\Academics\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Academics\Models\Department;

class DepartmentsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Departments', [
            'departments' => Department::withCount('programs')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:16', 'unique:departments,code'],
            'name' => ['required', 'string', 'max:150'],
            'head_of_dept' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
        ]);

        Department::create($data + ['is_active' => true]);

        return back()->with('flash', ['success' => 'Department created.']);
    }

    public function update(Request $request, Department $department): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:16', 'unique:departments,code,'.$department->id],
            'name' => ['required', 'string', 'max:150'],
            'head_of_dept' => ['nullable', 'string', 'max:150'],
            'description' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $department->update($data);

        return back()->with('flash', ['success' => 'Department updated.']);
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->programs()->exists()) {
            return back()->withErrors(['code' => 'Department has active programmes and cannot be removed.']);
        }
        $department->delete();

        return back()->with('flash', ['success' => 'Department removed.']);
    }
}
