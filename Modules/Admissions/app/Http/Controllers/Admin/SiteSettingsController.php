<?php

namespace Modules\Admissions\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Models\SiteSetting;

class SiteSettingsController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/SiteSettings', [
            'groups' => SiteSetting::orderBy('group')->orderBy('sort_order')->get()->groupBy('group'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'values' => ['required', 'array'],
            'values.*' => ['nullable', 'string', 'max:2000'],
        ]);

        foreach ($data['values'] as $key => $value) {
            SiteSetting::where('key', $key)->update(['value' => $value]);
        }

        // Bust the cache once for the whole batch.
        \Illuminate\Support\Facades\Cache::forget(SiteSetting::CACHE_KEY);

        return back()->with('flash', ['success' => 'Site settings saved.']);
    }
}
