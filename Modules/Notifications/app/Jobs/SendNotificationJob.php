<?php

namespace Modules\Notifications\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Notifications\Models\NotificationLog;
use Modules\Notifications\Models\NotificationTemplate;
use Modules\Notifications\Services\MailManager;
use Modules\Notifications\Services\SmsManager;
use Modules\Notifications\Services\WhatsappManager;

/**
 * One job per (event, channel, recipient). The listener dispatches one of these
 * per active channel for a fired event. Persists outcome to notification_logs.
 */
class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct(
        public string $event,
        public string $channel,
        public string $recipient,
        public ?int $userId,
        public array $context,
        public ?int $templateId = null,
    ) {
        // Route to the `notifications` queue. Production: QUEUE_CONNECTION=redis
        // and run `php artisan queue:work redis --queue=notifications`.
        $this->onQueue('notifications');
    }

    public function handle(
        SmsManager $smsManager,
        WhatsappManager $whatsappManager,
        MailManager $mailManager,
    ): void {
        $template = $this->templateId
            ? NotificationTemplate::find($this->templateId)
            : NotificationTemplate::where('event', $this->event)
                ->where('channel', $this->channel)
                ->where('is_active', true)
                ->first();

        if (! $template) {
            $this->logFailure('Template missing or inactive.');

            return;
        }

        $renderedBody = $template->render($this->context);
        $renderedSubject = $template->renderSubject($this->context);

        match ($this->channel) {
            NotificationTemplate::CHANNEL_SMS => $this->sendSms($smsManager, $template, $renderedBody),
            NotificationTemplate::CHANNEL_EMAIL => $this->sendEmail($mailManager, $template, $renderedSubject ?? 'SVNC Admissions', $renderedBody),
            NotificationTemplate::CHANNEL_WHATSAPP => $this->sendWhatsapp($whatsappManager, $template, $renderedBody),
            default => $this->logFailure("Unknown channel '{$this->channel}'."),
        };
    }

    protected function sendSms(SmsManager $manager, NotificationTemplate $template, string $body): void
    {
        $driver = $manager->activeDriver();
        if (! $driver) {
            $this->logFailure('No active SMS provider configured.');

            return;
        }

        $result = $driver->send($this->recipient, $body, $template->dlt_template_id);
        $this->persistOutcome($template, $driver->code(), $body, $result, $manager->activeProvider()->isStub());
    }

    protected function sendWhatsapp(WhatsappManager $manager, NotificationTemplate $template, string $body): void
    {
        $driver = $manager->activeDriver();
        if (! $driver) {
            $this->logFailure('No active WhatsApp provider configured.');

            return;
        }
        if (! $template->whatsapp_template_name) {
            $this->logFailure('Template missing whatsapp_template_name.');

            return;
        }

        $vars = array_map(fn ($v) => (string) $v, array_values($this->context));
        $result = $driver->sendTemplate($this->recipient, $template->whatsapp_template_name, $vars, $body);
        $this->persistOutcome($template, $driver->code(), $body, $result, $manager->activeProvider()->isStub());
    }

    protected function sendEmail(MailManager $manager, NotificationTemplate $template, string $subject, string $body): void
    {
        $result = $manager->send($this->recipient, $subject, $body);
        $this->persistOutcome($template, 'smtp', $body, $result, false);
    }

    protected function persistOutcome(
        NotificationTemplate $template,
        string $providerCode,
        string $body,
        \Modules\Notifications\Contracts\SendResult $result,
        bool $isStub,
    ): void {
        NotificationLog::create([
            'event' => $this->event,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'user_id' => $this->userId,
            'notification_template_id' => $template->id,
            'provider_code' => $providerCode,
            'provider_message_id' => $result->providerMessageId,
            'status' => $result->success
                ? ($isStub ? NotificationLog::STATUS_STUB : NotificationLog::STATUS_SENT)
                : NotificationLog::STATUS_FAILED,
            'rendered_body' => $body,
            'error' => $result->error,
            'context' => $this->context,
            'sent_at' => $result->success ? now() : null,
        ]);
    }

    protected function logFailure(string $reason): void
    {
        NotificationLog::create([
            'event' => $this->event,
            'channel' => $this->channel,
            'recipient' => $this->recipient,
            'user_id' => $this->userId,
            'notification_template_id' => $this->templateId,
            'status' => NotificationLog::STATUS_FAILED,
            'error' => $reason,
            'context' => $this->context,
        ]);
    }
}
