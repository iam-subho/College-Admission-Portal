<?php

namespace Modules\Notifications\Contracts;

interface SmsDriverContract
{
    public function code(): string;

    /**
     * Send a transactional SMS. India: $dltTemplateId is required for live; in
     * stub mode it's logged and accepted regardless.
     *
     * @param  string  $toE164  e.g. "+919876543210"
     * @param  string  $body    the rendered text (already variable-substituted)
     * @param  string|null  $dltTemplateId  DLT template id (India regulatory)
     */
    public function send(string $toE164, string $body, ?string $dltTemplateId = null): SendResult;
}
