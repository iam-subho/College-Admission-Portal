<?php

namespace Modules\Payments\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Payments\Contracts\PaymentGatewayContract;
use Modules\Payments\Models\PaymentGateway;
use RuntimeException;

class PaymentGatewayManager
{
    /**
     * Resolve the active gateway driver by code.
     */
    public function driver(string $code): PaymentGatewayContract
    {
        $gateway = PaymentGateway::where('code', $code)
            ->where('is_active', true)
            ->first();

        if (! $gateway) {
            throw new RuntimeException("No active payment gateway found with code: {$code}");
        }

        return $this->makeDriver($gateway);
    }

    /**
     * Resolve a driver for any gateway row (active or not) — used by reconcile
     * and admin test-connection actions.
     */
    public function driverFor(PaymentGateway $gateway): PaymentGatewayContract
    {
        return $this->makeDriver($gateway);
    }

    /** @return Collection<int, PaymentGateway> */
    public function activeGateways(): Collection
    {
        return PaymentGateway::where('is_active', true)
            ->orderBy('priority')
            ->get();
    }

    protected function makeDriver(PaymentGateway $gateway): PaymentGatewayContract
    {
        $registry = config('payments.drivers', []);
        $class = $registry[$gateway->code] ?? null;

        if (! $class || ! class_exists($class)) {
            throw new RuntimeException("No driver class registered for gateway: {$gateway->code}");
        }

        return app($class, ['gateway' => $gateway]);
    }
}
