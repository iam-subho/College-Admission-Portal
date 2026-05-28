<?php

use Illuminate\Support\Facades\Queue;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\Application;
use Modules\Notifications\Events\ApplicationSubmittedEvent;
use Modules\Notifications\Jobs\SendNotificationJob;
use Modules\Notifications\Models\NotificationLog;
use Modules\Notifications\Models\NotificationTemplate;
use Modules\Notifications\Services\MailManager;
use Modules\Notifications\Services\SmsManager;
use Modules\Notifications\Services\WhatsappManager;
use Modules\Students\Models\Student;
use Modules\Users\Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

it('fires SendNotificationJob per active channel when ApplicationSubmittedEvent dispatches', function () {
    NotificationTemplate::create([
        'event' => 'application_submitted',
        'channel' => 'sms',
        'body' => 'Hi {{name}}',
        'is_active' => true,
    ]);
    NotificationTemplate::create([
        'event' => 'application_submitted',
        'channel' => 'email',
        'subject' => 'App {{application_number}}',
        'body' => 'Hi {{name}}',
        'is_active' => true,
    ]);
    NotificationTemplate::create([
        'event' => 'application_submitted',
        'channel' => 'whatsapp',
        'body' => 'Hi {{name}}',
        'is_active' => false,
    ]);

    Queue::fake();

    $student = Student::factory()->create();
    $student->user->update(['mobile' => '9876543210']);

    $app = Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => Program::factory()->create()->id,
        'academic_session_id' => AcademicSession::factory()->active()->create()->id,
        'status' => Application::STATUS_SUBMITTED,
        'application_number' => 'SVNC/UG/2026/0001',
    ]);

    event(new ApplicationSubmittedEvent($app));

    // 2 channels active (sms + email), whatsapp skipped
    Queue::assertPushed(SendNotificationJob::class, 2);
});

it('renders the template body and logs to notification_logs', function () {
    $template = NotificationTemplate::create([
        'event' => 'application_submitted',
        'channel' => 'email',
        'subject' => 'App {{application_number}} received',
        'body' => "Hi {{name}},\n\nYour application {{application_number}} is recorded.",
        'is_active' => true,
    ]);

    $mockMail = Mockery::mock(MailManager::class);
    $mockMail->shouldReceive('send')
        ->once()
        ->andReturn(\Modules\Notifications\Contracts\SendResult::ok('mock_msg_1'));
    app()->instance(MailManager::class, $mockMail);

    $job = new SendNotificationJob(
        event: 'application_submitted',
        channel: 'email',
        recipient: 'student@example.com',
        userId: null,
        context: ['name' => 'Subho', 'application_number' => 'SVNC/UG/2026/000001'],
        templateId: $template->id,
    );

    $job->handle(app(SmsManager::class), app(WhatsappManager::class), $mockMail);

    $log = NotificationLog::first();
    expect($log)->not->toBeNull();
    expect($log->channel)->toBe('email');
    expect($log->status)->toBe('sent');
    expect($log->rendered_body)->toContain('Hi Subho');
    expect($log->rendered_body)->toContain('SVNC/UG/2026/000001');
});

it('logs failure when no SMS provider is active', function () {
    NotificationTemplate::create([
        'event' => 'application_submitted',
        'channel' => 'sms',
        'body' => 'test',
        'is_active' => true,
    ]);

    $job = new SendNotificationJob(
        event: 'application_submitted',
        channel: 'sms',
        recipient: '+919876543210',
        userId: null,
        context: ['name' => 'A'],
    );

    $job->handle(app(SmsManager::class), app(WhatsappManager::class), app(MailManager::class));

    $log = NotificationLog::first();
    expect($log->status)->toBe('failed');
    expect($log->error)->toBe('No active SMS provider configured.');
});

it('marks SMS sent as stub when active provider is in stub mode', function () {
    \Modules\Notifications\Models\SmsProvider::create([
        'code' => 'msg91',
        'display_name' => 'MSG91',
        'mode' => 'stub',
        'is_active' => true,
        'priority' => 1,
    ]);
    NotificationTemplate::create([
        'event' => 'application_submitted',
        'channel' => 'sms',
        'body' => 'Hi {{name}}',
        'is_active' => true,
    ]);

    $job = new SendNotificationJob(
        event: 'application_submitted',
        channel: 'sms',
        recipient: '+919876543210',
        userId: null,
        context: ['name' => 'Test'],
    );

    $job->handle(app(SmsManager::class), app(WhatsappManager::class), app(MailManager::class));

    $log = NotificationLog::first();
    expect($log->status)->toBe('stub');
    expect($log->provider_code)->toBe('msg91');
    expect($log->rendered_body)->toBe('Hi Test');
});
