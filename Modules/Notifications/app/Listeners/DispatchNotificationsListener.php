<?php

namespace Modules\Notifications\Listeners;

use Modules\Notifications\Events\NotifiableEvent;
use Modules\Notifications\Jobs\SendNotificationJob;
use Modules\Notifications\Models\NotificationTemplate;

/**
 * Single listener subscribed to all NotifiableEvent dispatches. Looks up the
 * active templates per channel for the event key and queues a SendNotificationJob
 * per (channel, recipient address). One listener instead of one per event
 * keeps wiring simple.
 */
class DispatchNotificationsListener
{
    public function handle(NotifiableEvent $event): void
    {
        $user = $event->recipient();
        if (! $user) {
            return;
        }

        $eventKey = $event->eventKey();
        $context = $event->variables();

        $templates = NotificationTemplate::where('event', $eventKey)
            ->where('is_active', true)
            ->get();

        foreach ($templates as $template) {
            $address = $this->addressFor($template->channel, $user);
            if (! $address) {
                continue;
            }

            SendNotificationJob::dispatch(
                event: $eventKey,
                channel: $template->channel,
                recipient: $address,
                userId: $user->id,
                context: $context,
                templateId: $template->id,
            );
        }
    }

    protected function addressFor(string $channel, $user): ?string
    {
        return match ($channel) {
            NotificationTemplate::CHANNEL_SMS, NotificationTemplate::CHANNEL_WHATSAPP => $this->normaliseE164($user->mobile),
            NotificationTemplate::CHANNEL_EMAIL => $user->email ?? null,
            default => null,
        };
    }

    protected function normaliseE164(?string $mobile): ?string
    {
        if (blank($mobile)) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', $mobile);
        if ($digits === '') {
            return null;
        }
        if (strlen($digits) === 10) {
            $digits = '91'.$digits;
        }

        return '+'.$digits;
    }
}
