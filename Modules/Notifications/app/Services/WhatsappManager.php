<?php

namespace Modules\Notifications\Services;

use Modules\Notifications\Contracts\WhatsappDriverContract;
use Modules\Notifications\Models\WhatsappProvider;
use Modules\Notifications\Services\Whatsapp\Drivers\GupshupWhatsappDriver;

class WhatsappManager
{
    public const DRIVERS = [
        'gupshup' => GupshupWhatsappDriver::class,
    ];

    public function activeProvider(): ?WhatsappProvider
    {
        return WhatsappProvider::where('is_active', true)
            ->orderBy('priority')
            ->first();
    }

    public function driverFor(WhatsappProvider $provider): WhatsappDriverContract
    {
        $class = self::DRIVERS[$provider->code] ?? null;
        abort_if(! $class, 500, "No WhatsApp driver registered for code '{$provider->code}'.");

        return new $class($provider);
    }

    public function activeDriver(): ?WhatsappDriverContract
    {
        $provider = $this->activeProvider();

        return $provider ? $this->driverFor($provider) : null;
    }
}
