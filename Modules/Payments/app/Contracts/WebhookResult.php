<?php

namespace Modules\Payments\Contracts;

class WebhookResult
{
    public function __construct(
        public bool $signatureValid,
        public string $eventType,
        public ?string $gatewayTxnId,
        public ?string $gatewayOrderId,
        public string $paymentStatus, // 'paid' | 'failed' | 'unknown'
        public ?float $amount,
        public ?string $method,
        public array $rawPayload,
        public ?string $idempotencyKey = null,
        public ?string $error = null,
    ) {}

    public static function invalid(string $reason, array $payload = []): self
    {
        return new self(
            signatureValid: false,
            eventType: 'unknown',
            gatewayTxnId: null,
            gatewayOrderId: null,
            paymentStatus: 'unknown',
            amount: null,
            method: null,
            rawPayload: $payload,
            error: $reason,
        );
    }
}
