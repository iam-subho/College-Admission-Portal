<?php

namespace Modules\Documents\Services\Digilocker;

use Modules\Documents\Services\Digilocker\Drivers\StubDigilockerDriver;
use RuntimeException;

class DigilockerManager
{
    /**
     * @var array<string, class-string<DigilockerDriverContract>>
     */
    protected array $drivers = [
        'stub' => StubDigilockerDriver::class,
    ];

    public function driver(?string $code = null): DigilockerDriverContract
    {
        $code ??= config('digilocker.default', 'stub');

        if (! isset($this->drivers[$code])) {
            throw new RuntimeException("DigiLocker driver [{$code}] not registered.");
        }

        return app($this->drivers[$code]);
    }

    /**
     * @param  class-string<DigilockerDriverContract>  $driverClass
     */
    public function register(string $code, string $driverClass): void
    {
        $this->drivers[$code] = $driverClass;
    }
}
