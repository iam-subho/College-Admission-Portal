<?php

use App\Models\User;
use Illuminate\Support\Facades\Schema;
use Modules\Audit\Services\DpdpConsentRecorder;
use Modules\Users\Database\Seeders\RoleSeeder;
use Modules\Users\Models\DpdpConsent;
use Spatie\Activitylog\Models\Activity;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

// ---------------------- AUDIT LOG ----------------------

it('captures before/after diff when an audited model is updated', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $session = \Modules\Admissions\Models\AcademicSession::create([
        'code' => '2026-27',
        'name' => '2026-27',
        'application_open_date' => now(),
        'application_close_date' => now()->addMonth(),
        'is_active' => true,
    ]);

    $dept = \Modules\Academics\Models\Department::create([
        'name' => 'Test Department',
        'code' => 'TD',
    ]);

    $programme = \Modules\Academics\Models\Program::create([
        'department_id' => $dept->id,
        'code' => 'BSC-CS',
        'name' => 'BSc Computer Science',
        'type' => 'UG',
        'duration_years' => 3,
        'intake_capacity' => 60,
        'is_active' => true,
    ]);

    $rule = \Modules\Admissions\Models\EligibilityRule::create([
        'program_id' => $programme->id,
        'rule_type' => 'min_class12_percent',
        'params' => ['value' => 45],
        'label' => 'Initial',
        'is_active' => true,
    ]);

    $this->actingAs($admin);

    $rule->update(['params' => ['value' => 60]]);

    $activity = Activity::where('subject_type', \Modules\Admissions\Models\EligibilityRule::class)
        ->where('subject_id', $rule->id)
        ->where('event', 'updated')
        ->latest('id')
        ->first();

    expect($activity)->not->toBeNull();
    expect($activity->log_name)->toBe('eligibility_rule');
    expect($activity->properties->get('attributes')['params']['value'] ?? null)->toBe(60);
    expect($activity->properties->get('old')['params']['value'] ?? null)->toBe(45);
});

it('hides the audit log page from students', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $this->actingAs($student)
        ->get('/admin/audit-log')
        ->assertForbidden();
});

it('renders the audit log page for admins', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/admin/audit-log')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/AuditLog'));
});

// ---------------------- RATE LIMITS ----------------------

it('blocks login after ten rapid attempts from the same IP+email', function () {
    $user = User::factory()->create([
        'email' => 'rate@example.test',
        'password' => bcrypt('Secret123'),
    ]);
    $user->assignRole('student');

    foreach (range(1, 10) as $i) {
        $this->post('/login', [
            'email' => 'rate@example.test',
            'password' => 'WrongPassword'.$i,
        ]);
    }

    $response = $this->post('/login', [
        'email' => 'rate@example.test',
        'password' => 'WrongPassword-final',
    ]);

    expect($response->status())->toBe(429);
});

// ---------------------- SECURITY HEADERS ----------------------

it('emits hardening headers on every web response', function () {
    $response = $this->get('/');

    expect($response->headers->get('X-Frame-Options'))->toBe('SAMEORIGIN');
    expect($response->headers->get('X-Content-Type-Options'))->toBe('nosniff');
    expect($response->headers->get('Referrer-Policy'))->toBe('strict-origin-when-cross-origin');
    expect($response->headers->get('Permissions-Policy'))->not->toBeNull();
});

it('emits a content security policy when enabled', function () {
    config(['security.csp.enabled' => true, 'security.csp.report_only' => false]);

    $response = $this->get('/');

    expect($response->headers->get('Content-Security-Policy'))->toContain("default-src 'self'");
});

// ---------------------- DPDP CONSENT ----------------------

it('records a DPDP consent row on registration', function () {
    $payload = [
        'name' => 'Consent Test',
        'email' => 'consent@example.test',
        'mobile' => '9876500001',
        'password' => 'Secret123',
        'password_confirmation' => 'Secret123',
        'dpdp_consent' => true,
    ];

    $this->post('/register', $payload)->assertRedirect();

    $user = User::where('email', 'consent@example.test')->first();
    expect($user)->not->toBeNull();

    $consent = DpdpConsent::where('user_id', $user->id)->first();
    expect($consent)->not->toBeNull();
    expect($consent->scope)->toBe(DpdpConsent::SCOPE_REGISTRATION);
    expect($consent->ip)->not->toBeNull();
});

it('records a consent via the recorder service for arbitrary scopes', function () {
    $user = User::factory()->create();

    $consent = app(DpdpConsentRecorder::class)->record(
        scope: DpdpConsent::SCOPE_PAYMENT,
        userId: $user->id,
        metadata: ['order_number' => 'ORD-TEST-1'],
    );

    expect($consent->scope)->toBe('payment');
    expect($consent->user_id)->toBe($user->id);
    expect($consent->metadata)->toBe(['order_number' => 'ORD-TEST-1']);
    expect($consent->accepted_at)->not->toBeNull();
});

it('renders the admin DPDP viewer for admins', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $this->actingAs($admin)
        ->get('/admin/dpdp-consents')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Admin/DpdpConsents'));
});

// ---------------------- COMPOSITE INDEXES ----------------------

it('has the phase-12 composite indexes defined', function () {
    $driver = \Illuminate\Support\Facades\DB::getDriverName();

    if ($driver === 'sqlite') {
        $sql = "SELECT name FROM sqlite_master WHERE type='index' AND name=?";
        expect(\Illuminate\Support\Facades\DB::select($sql, ['apps_status_payment_idx']))->not->toBeEmpty();
        expect(\Illuminate\Support\Facades\DB::select($sql, ['notif_status_channel_idx']))->not->toBeEmpty();
        expect(\Illuminate\Support\Facades\DB::select($sql, ['dpdp_accepted_at_idx']))->not->toBeEmpty();

        return;
    }

    expect(\Illuminate\Support\Facades\DB::select('SHOW INDEX FROM `applications` WHERE Key_name = ?', ['apps_status_payment_idx']))->not->toBeEmpty();
    expect(\Illuminate\Support\Facades\DB::select('SHOW INDEX FROM `notification_logs` WHERE Key_name = ?', ['notif_status_channel_idx']))->not->toBeEmpty();
    expect(\Illuminate\Support\Facades\DB::select('SHOW INDEX FROM `dpdp_consents` WHERE Key_name = ?', ['dpdp_accepted_at_idx']))->not->toBeEmpty();
});
