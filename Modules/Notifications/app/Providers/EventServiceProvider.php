<?php

namespace Modules\Notifications\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Modules\Notifications\Events\AcceptanceReminderEvent;
use Modules\Notifications\Events\AdmissionConfirmedEvent;
use Modules\Notifications\Events\AdmitCardReleasedEvent;
use Modules\Notifications\Events\ApplicationSubmittedEvent;
use Modules\Notifications\Events\DocumentApprovedEvent;
use Modules\Notifications\Events\DocumentRejectedEvent;
use Modules\Notifications\Events\EligibilityOverriddenEvent;
use Modules\Notifications\Events\MeritPublishedNotificationEvent;
use Modules\Notifications\Events\PaymentReceivedEvent;
use Modules\Notifications\Events\RefundCompletedEvent;
use Modules\Notifications\Events\SeatAllottedEvent;
use Modules\Notifications\Events\WithdrawalApprovedEvent;
use Modules\Notifications\Listeners\DispatchNotificationsListener;

class EventServiceProvider extends ServiceProvider
{
    /**
     * Every NotifiableEvent dispatches the same single listener which then
     * fans out to sms / email / whatsapp jobs based on configured templates.
     *
     * @var array<string, array<int, string>>
     */
    protected $listen = [
        ApplicationSubmittedEvent::class => [DispatchNotificationsListener::class],
        PaymentReceivedEvent::class => [DispatchNotificationsListener::class],
        AdmitCardReleasedEvent::class => [DispatchNotificationsListener::class],
        DocumentApprovedEvent::class => [DispatchNotificationsListener::class],
        DocumentRejectedEvent::class => [DispatchNotificationsListener::class],
        EligibilityOverriddenEvent::class => [DispatchNotificationsListener::class],
        MeritPublishedNotificationEvent::class => [DispatchNotificationsListener::class],
        SeatAllottedEvent::class => [DispatchNotificationsListener::class],
        AcceptanceReminderEvent::class => [DispatchNotificationsListener::class],
        AdmissionConfirmedEvent::class => [DispatchNotificationsListener::class],
        WithdrawalApprovedEvent::class => [DispatchNotificationsListener::class],
        RefundCompletedEvent::class => [DispatchNotificationsListener::class],
    ];

    protected static $shouldDiscoverEvents = false;

    protected function configureEmailVerification(): void {}
}
