<?php

namespace Modules\Notifications\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Notifications\Models\SmsProvider;

class SmsProvidersController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/SmsProviders', [
            'providers' => SmsProvider::orderBy('priority')->get()->map(fn (SmsProvider $p) => [
                'id' => $p->id,
                'code' => $p->code,
                'display_name' => $p->display_name,
                'mode' => $p->mode,
                'is_active' => $p->is_active,
                'priority' => $p->priority,
                'config' => $p->config_encrypted ? array_keys($p->config) : [],
            ]),
            'codes' => array_keys(\Modules\Notifications\Services\SmsManager::DRIVERS),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $config = $data['config'] ?? [];
        unset($data['config']);

        $provider = SmsProvider::create($data + ['is_active' => false]);
        $provider->config = $config;
        $provider->save();

        return back()->with('flash', ['success' => 'SMS provider created.']);
    }

    public function update(Request $request, SmsProvider $provider): RedirectResponse
    {
        $data = $this->validatePayload($request, $provider);
        $config = $data['config'] ?? null;
        unset($data['config']);

        $provider->update($data);
        if (! empty($config)) {
            $provider->config = $config;
            $provider->save();
        }

        return back()->with('flash', ['success' => 'SMS provider updated.']);
    }

    public function toggle(SmsProvider $provider): RedirectResponse
    {
        // Only one active at a time — flip others off when activating.
        if (! $provider->is_active) {
            SmsProvider::where('id', '!=', $provider->id)->update(['is_active' => false]);
        }
        $provider->update(['is_active' => ! $provider->is_active]);

        return back()->with('flash', ['success' => $provider->display_name.' is now '.($provider->is_active ? 'active' : 'disabled').'.']);
    }

    public function destroy(SmsProvider $provider): RedirectResponse
    {
        $provider->delete();

        return back()->with('flash', ['success' => 'SMS provider deleted.']);
    }

    protected function validatePayload(Request $request, ?SmsProvider $provider = null): array
    {
        $codeRule = $provider ? 'unique:sms_providers,code,'.$provider->id : 'unique:sms_providers,code';

        return $request->validate([
            'code' => ['required', 'string', 'max:32', $codeRule, \Illuminate\Validation\Rule::in(array_keys(\Modules\Notifications\Services\SmsManager::DRIVERS))],
            'display_name' => ['required', 'string', 'max:100'],
            'mode' => ['required', \Illuminate\Validation\Rule::in(['stub', 'test', 'live'])],
            'priority' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'config' => ['nullable', 'array'],
        ]);
    }
}
