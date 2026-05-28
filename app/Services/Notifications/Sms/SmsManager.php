<?php

namespace App\Services\Notifications\Sms;

use App\Services\Notifications\Sms\Drivers\LogStubDriver;
use InvalidArgumentException;

class SmsManager
{
    /**
     * @var array<string, class-string<SmsDriverContract>>
     */
    protected array $drivers = [
        'log_stub' => LogStubDriver::class,
    ];

    public function driver(?string $code = null): SmsDriverContract
    {
        $code ??= config('sms.default', 'log_stub');

        if (! isset($this->drivers[$code])) {
            throw new InvalidArgumentException("SMS driver [{$code}] is not registered.");
        }

        return app($this->drivers[$code]);
    }

    /**
     * @param  class-string<SmsDriverContract>  $driverClass
     */
    public function register(string $code, string $driverClass): void
    {
        $this->drivers[$code] = $driverClass;
    }
}
