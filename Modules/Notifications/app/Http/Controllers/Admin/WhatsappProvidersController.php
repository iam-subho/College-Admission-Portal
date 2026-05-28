<?php

namespace Modules\Notifications\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Notifications\Models\WhatsappProvider;

class WhatsappProvidersController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/WhatsappProviders', [
            'providers' => WhatsappProvider::orderBy('priority')->get()->map(fn (WhatsappProvider $p) => [
                'id' => $p->id,
                'code' => $p->code,
                'display_name' => $p->display_name,
                'mode' => $p->mode,
                'is_active' => $p->is_active,
                'priority' => $p->priority,
                'config' => $p->config_encrypted ? array_keys($p->config) : [],
            ]),
            'codes' => array_keys(\Modules\Notifications\Services\WhatsappManager::DRIVERS),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validatePayload($request);
        $config = $data['config'] ?? [];
        unset($data['config']);

        $provider = WhatsappProvider::create($data + ['is_active' => false]);
        $provider->config = $config;
        $provider->save();

        return back()->with('flash', ['success' => 'WhatsApp provider created.']);
    }

    public function update(Request $request, WhatsappProvider $provider): RedirectResponse
    {
        $data = $this->validatePayload($request, $provider);
        $config = $data['config'] ?? null;
        unset($data['config']);

        $provider->update($data);
        if (! empty($config)) {
            $provider->config = $config;
            $provider->save();
        }

        return back()->with('flash', ['success' => 'WhatsApp provider updated.']);
    }

    public function toggle(WhatsappProvider $provider): RedirectResponse
    {
        if (! $provider->is_active) {
            WhatsappProvider::where('id', '!=', $provider->id)->update(['is_active' => false]);
        }
        $provider->update(['is_active' => ! $provider->is_active]);

        return back()->with('flash', ['success' => $provider->display_name.' is now '.($provider->is_active ? 'active' : 'disabled').'.']);
    }

    public function destroy(WhatsappProvider $provider): RedirectResponse
    {
        $provider->delete();

        return back()->with('flash', ['success' => 'WhatsApp provider deleted.']);
    }

    protected function validatePayload(Request $request, ?WhatsappProvider $provider = null): array
    {
        $codeRule = $provider ? 'unique:whatsapp_providers,code,'.$provider->id : 'unique:whatsapp_providers,code';

        return $request->validate([
            'code' => ['required', 'string', 'max:32', $codeRule, \Illuminate\Validation\Rule::in(array_keys(\Modules\Notifications\Services\WhatsappManager::DRIVERS))],
            'display_name' => ['required', 'string', 'max:100'],
            'mode' => ['required', \Illuminate\Validation\Rule::in(['stub', 'test', 'live'])],
            'priority' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'config' => ['nullable', 'array'],
        ]);
    }
}
