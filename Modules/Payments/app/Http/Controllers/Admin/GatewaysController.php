<?php

namespace Modules\Payments\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Payments\Models\PaymentGateway;

class GatewaysController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Gateways', [
            'gateways' => PaymentGateway::orderBy('priority')->get()->map(fn ($g) => [
                'id' => $g->id,
                'code' => $g->code,
                'display_name' => $g->display_name,
                'is_active' => $g->is_active,
                'mode' => $g->mode,
                'priority' => $g->priority,
                'convenience_fee_rule' => $g->convenience_fee_rule,
                'has_config' => ! blank($g->config_encrypted),
            ]),
            'available_codes' => array_keys(config('payments.drivers', [])),
            'modes' => [PaymentGateway::MODE_STUB, PaymentGateway::MODE_TEST, PaymentGateway::MODE_LIVE],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'unique:payment_gateways,code', 'in:'.implode(',', array_keys(config('payments.drivers', [])))],
            'display_name' => ['required', 'string', 'max:80'],
            'mode' => ['required', 'in:stub,test,live'],
            'priority' => ['nullable', 'integer'],
            'convenience_fee_rule' => ['nullable', 'string', 'max:80'],
        ]);

        PaymentGateway::create($data + ['is_active' => true]);

        return back()->with('flash', ['success' => 'Gateway added.']);
    }

    public function update(Request $request, PaymentGateway $gateway): RedirectResponse
    {
        $data = $request->validate([
            'display_name' => ['sometimes', 'string', 'max:80'],
            'mode' => ['sometimes', 'in:stub,test,live'],
            'priority' => ['sometimes', 'integer'],
            'convenience_fee_rule' => ['nullable', 'string', 'max:80'],
            'config' => ['sometimes', 'array'],
            'config.key_id' => ['nullable', 'string', 'max:200'],
            'config.key_secret' => ['nullable', 'string', 'max:200'],
            'config.webhook_secret' => ['nullable', 'string', 'max:200'],
        ]);

        if (array_key_exists('config', $data)) {
            $gateway->config = array_filter($data['config'], fn ($v) => $v !== null && $v !== '');
            unset($data['config']);
        }

        $gateway->fill($data)->save();

        return back()->with('flash', ['success' => 'Gateway updated.']);
    }

    public function toggle(PaymentGateway $gateway): RedirectResponse
    {
        $gateway->forceFill(['is_active' => ! $gateway->is_active])->save();

        return back()->with('flash', [
            'success' => "Gateway {$gateway->display_name} ".($gateway->is_active ? 'activated.' : 'deactivated.'),
        ]);
    }
}
