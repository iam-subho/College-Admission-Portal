<?php

namespace App\Services\Notifications\Sms;

class SendResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $providerId = null,
        public readonly ?string $error = null,
    ) {}

    public static function ok(?string $providerId = null): self
    {
        return new self(true, $providerId);
    }

    public static function fail(string $error): self
    {
        return new self(false, null, $error);
    }
}
