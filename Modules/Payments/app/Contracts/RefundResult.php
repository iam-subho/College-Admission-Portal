<?php

namespace Modules\Payments\Contracts;

class RefundResult
{
    public function __construct(
        public bool $success,
        public ?string $gatewayRefundId,
        public string $status, // 'processing' | 'completed' | 'failed'
        public array $rawPayload,
        public ?string $error = null,
    ) {}

    public static function failure(string $reason, array $payload = []): self
    {
        return new self(false, null, 'failed', $payload, $reason);
    }
}
