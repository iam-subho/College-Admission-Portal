<?php

namespace Modules\Notifications\Services;

use Modules\Notifications\Contracts\SmsDriverContract;
use Modules\Notifications\Models\SmsProvider;
use Modules\Notifications\Services\Sms\Drivers\Msg91Driver;

class SmsManager
{
    /**
     * Map of provider code → driver class. Adding a new SMS provider:
     *   1. Drop a Driver class under Services\Sms\Drivers\
     *   2. Register the code → class mapping here
     *   3. Insert a sms_providers row via admin
     */
    public const DRIVERS = [
        'msg91' => Msg91Driver::class,
    ];

    public function activeProvider(): ?SmsProvider
    {
        return SmsProvider::where('is_active', true)
            ->orderBy('priority')
            ->first();
    }

    public function driverFor(SmsProvider $provider): SmsDriverContract
    {
        $class = self::DRIVERS[$provider->code] ?? null;
        abort_if(! $class, 500, "No SMS driver registered for code '{$provider->code}'.");

        return new $class($provider);
    }

    public function activeDriver(): ?SmsDriverContract
    {
        $provider = $this->activeProvider();

        return $provider ? $this->driverFor($provider) : null;
    }
}
