<?php

namespace Modules\Admissions\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Models\Notice;

class NoticesController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Notices', [
            'notices' => Notice::orderByDesc('notice_date')->orderByDesc('sort_order')->get(),
            'tabs' => Notice::tabs(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Notice::create($this->validateNotice($request));

        return back()->with('flash', ['success' => 'Notice added.']);
    }

    public function update(Request $request, Notice $notice): RedirectResponse
    {
        $notice->update($this->validateNotice($request));

        return back()->with('flash', ['success' => 'Notice updated.']);
    }

    public function toggle(Notice $notice): RedirectResponse
    {
        $notice->update(['is_active' => ! $notice->is_active]);

        return back()->with('flash', ['success' => $notice->is_active ? 'Notice published.' : 'Notice hidden.']);
    }

    public function destroy(Notice $notice): RedirectResponse
    {
        $notice->delete();

        return back()->with('flash', ['success' => 'Notice removed.']);
    }

    protected function validateNotice(Request $request): array
    {
        return $request->validate([
            'notice_date' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'tab' => ['required', 'in:'.implode(',', Notice::tabs())],
            'url' => ['nullable', 'url', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ]);
    }
}
