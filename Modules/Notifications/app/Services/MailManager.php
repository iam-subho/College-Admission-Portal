<?php

namespace Modules\Notifications\Services;

use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Modules\Notifications\Contracts\SendResult;

/**
 * Thin wrapper around Laravel's Mail facade. Sends a plain-text email body —
 * no Mailable class needed because our NotificationTemplate already produces
 * the rendered text.
 */
class MailManager
{
    public function send(string $toEmail, string $subject, string $body): SendResult
    {
        try {
            Mail::raw($body, function ($m) use ($toEmail, $subject) {
                $m->to($toEmail)->subject($subject);
            });

            return SendResult::ok('mail_'.Str::random(10));
        } catch (\Throwable $e) {
            return SendResult::fail('Mail send failed: '.$e->getMessage());
        }
    }
}
