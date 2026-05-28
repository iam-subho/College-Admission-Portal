<?php

namespace Modules\Notifications\Contracts;

interface WhatsappDriverContract
{
    public function code(): string;

    /**
     * Send a WhatsApp template message. WhatsApp Business API requires pre-approved
     * templates — admin configures the template_name per event.
     *
     * @param  string  $toE164         e.g. "+919876543210"
     * @param  string  $templateName   pre-approved WhatsApp template name
     * @param  array<int,string>  $vars  positional variables for the template body
     * @param  string  $renderedBody   the substituted plaintext (for logging)
     */
    public function sendTemplate(string $toE164, string $templateName, array $vars, string $renderedBody): SendResult;
}
