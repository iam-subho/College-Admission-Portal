<?php

namespace Modules\Payments\Contracts;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Modules\Payments\Models\PaymentOrder;
use Modules\Payments\Models\Refund;

/**
 * Implementations live under app/Services/Payments/Gateways/ (per module) and
 * are registered in config/payments.php → drivers. Adding a new gateway =
 * one new file implementing this contract + one row in `payment_gateways`.
 */
interface PaymentGatewayContract
{
    /** Returns the unique gateway code, matching the `payment_gateways.code`. */
    public function code(): string;

    /**
     * Create a payment order with the gateway. Must update $order with the
     * `gateway_order_id` and persist. Returns a payload to hand to the
     * client-side checkout SDK.
     *
     * @return array{checkout: array<string, mixed>, gateway_order_id: string}
     */
    public function createOrder(PaymentOrder $order): array;

    /**
     * Verify an inbound webhook request — signature check + extracted event.
     * MUST be side-effect-free; the action layer handles idempotent persistence.
     */
    public function verifyWebhook(Request $request): WebhookResult;

    /** Refund through the original gateway. */
    public function refund(Refund $refund): RefundResult;

    /**
     * Reconcile payments captured on the given date. Returns rows with at
     * least: gateway_order_id, gateway_txn_id, amount, status, method, paid_at.
     *
     * @return array<int, array<string, mixed>>
     */
    public function reconcile(Carbon $date): array;
}
