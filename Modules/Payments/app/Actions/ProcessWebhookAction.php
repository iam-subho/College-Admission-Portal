<?php

namespace Modules\Payments\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Admissions\Models\Application;
use Modules\Payments\Contracts\WebhookResult;
use Modules\Payments\Models\PaymentGateway;
use Modules\Payments\Models\PaymentOrder;
use Modules\Payments\Models\PaymentTransaction;
use Modules\Payments\Models\PaymentWebhook;
use Modules\Payments\Services\PaymentGatewayManager;

class ProcessWebhookAction
{
    public function __construct(protected PaymentGatewayManager $manager) {}

    public function execute(string $gatewayCode, Request $request): PaymentWebhook
    {
        $gateway = PaymentGateway::where('code', $gatewayCode)->firstOrFail();
        $result = $this->manager->driverFor($gateway)->verifyWebhook($request);

        return DB::transaction(function () use ($gateway, $result) {
            return $this->persist($gateway, $result);
        });
    }

    protected function persist(PaymentGateway $gateway, WebhookResult $result): PaymentWebhook
    {
        // Idempotent on idempotency_key (e.g. razorpay:pay_xxxx).
        if ($result->idempotencyKey) {
            $existing = PaymentWebhook::where('idempotency_key', $result->idempotencyKey)->first();
            if ($existing) {
                return $existing;
            }
        }

        $webhook = PaymentWebhook::create([
            'payment_gateway_id' => $gateway->id,
            'event_type' => $result->eventType,
            'gateway_txn_id' => $result->gatewayTxnId,
            'gateway_order_id' => $result->gatewayOrderId,
            'idempotency_key' => $result->idempotencyKey,
            'payload' => $result->rawPayload,
            'signature_valid' => $result->signatureValid,
            'processed' => false,
            'error' => $result->error,
        ]);

        if (! $result->signatureValid) {
            return $webhook;
        }

        $order = $result->gatewayOrderId
            ? PaymentOrder::where('gateway_order_id', $result->gatewayOrderId)->first()
            : null;

        if (! $order) {
            $webhook->forceFill(['error' => 'Order not found for gateway_order_id: '.$result->gatewayOrderId])->save();

            return $webhook;
        }

        if ($result->paymentStatus === 'paid') {
            $this->markPaid($order, $result);
        } elseif ($result->paymentStatus === 'failed') {
            $order->forceFill(['status' => PaymentOrder::STATUS_FAILED])->save();
        }

        $webhook->forceFill(['processed' => true])->save();

        return $webhook;
    }

    protected function markPaid(PaymentOrder $order, WebhookResult $result): void
    {
        if ($result->gatewayTxnId) {
            PaymentTransaction::firstOrCreate(
                ['gateway_txn_id' => $result->gatewayTxnId],
                [
                    'payment_order_id' => $order->id,
                    'status' => PaymentTransaction::STATUS_SUCCESS,
                    'amount' => $result->amount ?? $order->total,
                    'method' => $result->method,
                    'raw_payload' => $result->rawPayload,
                    'paid_at' => now(),
                ],
            );
        }

        if ($order->status !== PaymentOrder::STATUS_PAID) {
            $order->forceFill([
                'status' => PaymentOrder::STATUS_PAID,
                'paid_at' => now(),
            ])->save();
        }

        // Side effects are purpose-aware:
        //   application_fee → flip Application.payment_status to 'paid'
        //   admission_fee   → flip the linked SeatAllocation to 'admitted'
        if ($order->purpose === PaymentOrder::PURPOSE_APPLICATION_FEE) {
            $order->application?->forceFill([
                'payment_status' => Application::PAYMENT_PAID,
            ])->save();
        } elseif ($order->purpose === PaymentOrder::PURPOSE_ADMISSION_FEE) {
            \Modules\Seats\Models\SeatAllocation::query()
                ->where('admission_fee_order_id', $order->id)
                ->update([
                    'status' => \Modules\Seats\Models\SeatAllocation::STATUS_ADMITTED,
                    'admitted_at' => now(),
                ]);
        }

        // Fire notifications.
        event(new \Modules\Notifications\Events\PaymentReceivedEvent($order->fresh()));
        if ($order->purpose === PaymentOrder::PURPOSE_ADMISSION_FEE) {
            $alloc = \Modules\Seats\Models\SeatAllocation::where('admission_fee_order_id', $order->id)->first();
            if ($alloc) {
                event(new \Modules\Notifications\Events\AdmissionConfirmedEvent($alloc));
            }
        }
    }
}
