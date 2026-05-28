<?php

namespace App\Services\Notifications\Sms\Drivers;

use App\Services\Notifications\Sms\SendResult;
use App\Services\Notifications\Sms\SmsDriverContract;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogStubDriver implements SmsDriverContract
{
    public function code(): string
    {
        return 'log_stub';
    }

    public function send(string $toE164, string $dltTemplateId, array $vars): SendResult
    {
        $providerId = (string) Str::uuid();

        Log::channel(config('sms.log_channel', 'stack'))->info('[sms-stub] outbound', [
            'to' => $toE164,
            'template' => $dltTemplateId,
            'vars' => $vars,
            'provider_id' => $providerId,
        ]);

        return SendResult::ok($providerId);
    }
}
