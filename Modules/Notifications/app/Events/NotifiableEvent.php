<?php

namespace Modules\Notifications\Events;

use App\Models\User;

/**
 * Common shape every notification-triggering event implements. The
 * DispatchNotificationsListener uses these three accessors to render the
 * template body and to address the SMS / Email / WhatsApp recipient.
 */
interface NotifiableEvent
{
    /**
     * Event key (matches NotificationTemplate.event column).
     * Snake_case, stable, no version suffix.
     */
    public function eventKey(): string;

    /**
     * The user who will receive the notification (must have email + mobile
     * to be addressable on those channels). Returns null if the event has
     * no specific recipient.
     */
    public function recipient(): ?User;

    /**
     * Variable map used to render the template body / subject.
     *
     * @return array<string,scalar|null>
     */
    public function variables(): array;
}
