<?php

namespace App\Services\Notifications\Sms;

interface SmsDriverContract
{
    public function code(): string;

    /**
     * @param  array<string, string|int>  $vars
     */
    public function send(string $toE164, string $dltTemplateId, array $vars): SendResult;
}
