<?php

namespace Modules\Payments\Services\Gateways;

use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Modules\Payments\Contracts\PaymentGatewayContract;
use Modules\Payments\Contracts\RefundResult;
use Modules\Payments\Contracts\WebhookResult;
use Modules\Payments\Models\PaymentGateway;
use Modules\Payments\Models\PaymentOrder;
use Modules\Payments\Models\Refund;

class RazorpayDriver implements PaymentGatewayContract
{
    public function __construct(protected PaymentGateway $gateway) {}

    public function code(): string
    {
        return 'razorpay';
    }

    public function createOrder(PaymentOrder $order): array
    {
        if ($this->gateway->isStub()) {
            $gatewayOrderId = 'order_stub_'.Str::random(12);
            $order->forceFill([
                'gateway_order_id' => $gatewayOrderId,
                'status' => PaymentOrder::STATUS_PROCESSING,
                'initiated_at' => now(),
                'gateway_payload' => ['stub' => true, 'gateway_order_id' => $gatewayOrderId],
            ])->save();

            return [
                'gateway_order_id' => $gatewayOrderId,
                'checkout' => [
                    'gateway_code' => 'razorpay',
                    'mode' => 'stub',
                    'gateway_order_id' => $gatewayOrderId,
                    'internal_order_id' => $order->id,
                    'amount_paise' => (int) round($order->total * 100),
                    'currency' => $order->currency,
                    'callback_url' => route('webhooks.gateway', ['gateway' => 'razorpay']),
                ],
            ];
        }

        $response = $this->httpClient()
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => (int) round($order->total * 100),
                'currency' => $order->currency,
                'receipt' => $order->order_number,
                'notes' => [
                    'application_id' => (string) $order->application_id,
                    'purpose' => $order->purpose,
                ],
            ]);

        $response->throw();
        $body = $response->json();

        $order->forceFill([
            'gateway_order_id' => $body['id'],
            'status' => PaymentOrder::STATUS_PROCESSING,
            'initiated_at' => now(),
            'gateway_payload' => $body,
        ])->save();

        return [
            'gateway_order_id' => $body['id'],
            'checkout' => [
                'gateway_code' => 'razorpay',
                'mode' => $this->gateway->mode,
                'key' => $this->config('key_id'),
                'gateway_order_id' => $body['id'],
                'internal_order_id' => $order->id,
                'amount_paise' => (int) round($order->total * 100),
                'currency' => $order->currency,
                'name' => 'SVNC Admissions',
                'description' => 'Application Fee — '.$order->order_number,
                'callback_url' => route('payments.callback.razorpay'),
            ],
        ];
    }

    /**
     * Verify the signature returned by the Razorpay Checkout client-side handler.
     * Signature is HMAC-SHA256 of "{razorpay_order_id}|{razorpay_payment_id}"
     * using the gateway key_secret. Returns true on match.
     */
    public function verifyCheckoutSignature(string $razorpayOrderId, string $razorpayPaymentId, string $signature): bool
    {
        if ($this->gateway->isStub()) {
            return true;
        }

        $secret = $this->config('key_secret');
        if (blank($secret)) {
            return false;
        }

        $expected = hash_hmac('sha256', "{$razorpayOrderId}|{$razorpayPaymentId}", $secret);

        return hash_equals($expected, $signature);
    }

    public function verifyWebhook(Request $request): WebhookResult
    {
        $payload = $request->json()->all();
        $signature = (string) $request->header('X-Razorpay-Signature');

        if ($this->gateway->isStub()) {
            // Stub mode: accept the inbound payload as authoritative.
            return $this->parseEvent($payload, signatureValid: true);
        }

        $secret = $this->config('webhook_secret');
        if (blank($secret)) {
            return WebhookResult::invalid('Webhook secret not configured.', $payload);
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);
        $valid = hash_equals($expected, $signature);

        if (! $valid) {
            return WebhookResult::invalid('Signature mismatch.', $payload);
        }

        return $this->parseEvent($payload, signatureValid: true);
    }

    public function refund(Refund $refund): RefundResult
    {
        $txn = $refund->transaction;
        if (! $txn) {
            return RefundResult::failure('No source transaction recorded; cannot refund.');
        }

        if ($this->gateway->isStub()) {
            $id = 'rfnd_stub_'.Str::random(12);

            return new RefundResult(
                success: true,
                gatewayRefundId: $id,
                status: Refund::STATUS_COMPLETED,
                rawPayload: ['stub' => true, 'gateway_refund_id' => $id],
            );
        }

        $response = $this->httpClient()
            ->post("https://api.razorpay.com/v1/payments/{$txn->gateway_txn_id}/refund", [
                'amount' => (int) round($refund->amount * 100),
                'notes' => ['refund_id' => (string) $refund->id, 'reason' => (string) $refund->reason],
            ]);

        if (! $response->successful()) {
            return RefundResult::failure(
                'Razorpay refund failed: '.$response->body(),
                $response->json() ?? [],
            );
        }

        $body = $response->json();

        return new RefundResult(
            success: true,
            gatewayRefundId: $body['id'] ?? null,
            status: ($body['status'] ?? 'processed') === 'processed'
                ? Refund::STATUS_COMPLETED
                : Refund::STATUS_PROCESSING,
            rawPayload: $body,
        );
    }

    public function reconcile(Carbon $date): array
    {
        if ($this->gateway->isStub()) {
            return [];
        }

        $from = $date->copy()->startOfDay()->getTimestamp();
        $to = $date->copy()->endOfDay()->getTimestamp();

        $response = $this->httpClient()
            ->get('https://api.razorpay.com/v1/payments', [
                'from' => $from,
                'to' => $to,
                'count' => 100,
            ]);

        $response->throw();

        return collect($response->json('items', []))
            ->map(fn ($p) => [
                'gateway_order_id' => $p['order_id'] ?? null,
                'gateway_txn_id' => $p['id'] ?? null,
                'amount' => isset($p['amount']) ? $p['amount'] / 100 : null,
                'status' => $p['status'] ?? null,
                'method' => $p['method'] ?? null,
                'paid_at' => isset($p['created_at']) ? Carbon::createFromTimestamp($p['created_at']) : null,
                'raw' => $p,
            ])->all();
    }

    protected function parseEvent(array $payload, bool $signatureValid): WebhookResult
    {
        $event = (string) ($payload['event'] ?? 'unknown');
        $paymentEntity = data_get($payload, 'payload.payment.entity', []);

        $status = match (true) {
            in_array($event, ['payment.captured', 'order.paid'], true) => 'paid',
            $event === 'payment.failed' => 'failed',
            default => 'unknown',
        };

        $idempotencyKey = $paymentEntity['id'] ?? $payload['id'] ?? null;

        return new WebhookResult(
            signatureValid: $signatureValid,
            eventType: $event,
            gatewayTxnId: $paymentEntity['id'] ?? null,
            gatewayOrderId: $paymentEntity['order_id'] ?? null,
            paymentStatus: $status,
            amount: isset($paymentEntity['amount']) ? $paymentEntity['amount'] / 100 : null,
            method: $paymentEntity['method'] ?? null,
            rawPayload: $payload,
            idempotencyKey: $idempotencyKey ? "razorpay:{$idempotencyKey}" : null,
        );
    }

    protected function httpClient(): PendingRequest
    {
        return Http::withBasicAuth(
            $this->config('key_id', ''),
            $this->config('key_secret', ''),
        )->acceptJson()->timeout(20);
    }

    protected function config(string $key, mixed $default = null): mixed
    {
        return $this->gateway->config[$key] ?? $default;
    }
}
