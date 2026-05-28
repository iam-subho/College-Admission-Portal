<?php

namespace Modules\Admissions\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Models\ReservationCategory;

class ReservationCategoriesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Reservations', [
            'categories' => ReservationCategory::orderBy('ordering')->orderBy('id')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);

        ReservationCategory::create($data + ['is_active' => true]);

        return back()->with('flash', ['success' => 'Reservation category created.']);
    }

    public function update(Request $request, ReservationCategory $category): RedirectResponse
    {
        $data = $this->validatePayload($request, $category);

        $category->update($data);

        return back()->with('flash', ['success' => 'Reservation category updated.']);
    }

    public function toggle(ReservationCategory $category): RedirectResponse
    {
        $category->update(['is_active' => ! $category->is_active]);

        return back()->with('flash', [
            'success' => $category->name.' is now '.($category->is_active ? 'enabled' : 'disabled').'.',
        ]);
    }

    public function destroy(ReservationCategory $category): RedirectResponse
    {
        if ($category->matrixEntries()->exists()) {
            return back()->with('flash', [
                'error' => 'Cannot delete — this category is in use in one or more programme reservation matrices.',
            ]);
        }

        $category->delete();

        return back()->with('flash', ['success' => 'Reservation category deleted.']);
    }

    protected function validatePayload(Request $request, ?ReservationCategory $category = null): array
    {
        $codeRule = $category
            ? 'unique:reservation_categories,code,'.$category->id
            : 'unique:reservation_categories,code';

        return $request->validate([
            'code' => ['required', 'string', 'max:16', $codeRule],
            'name' => ['required', 'string', 'max:120'],
            'is_horizontal' => ['required', 'boolean'],
            'default_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ordering' => ['nullable', 'integer', 'min:0', 'max:1000'],
        ]);
    }
}
