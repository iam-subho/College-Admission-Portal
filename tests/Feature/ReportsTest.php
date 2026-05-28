<?php

use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\Application;
use Modules\Payments\Models\PaymentGateway;
use Modules\Payments\Models\PaymentOrder;
use Modules\Reports\ReportRegistry;
use Modules\Reports\Reports\ApplicationFunnelReport;
use Modules\Reports\Reports\DailyAdmissionReport;
use Modules\Reports\Reports\FeeCollectionRegister;
use Modules\Students\Models\Student;
use Modules\Users\Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makePaidAppForReport(Program $program, AcademicSession $session, ?\DateTimeInterface $submittedAt = null): Application
{
    $student = Student::factory()->create();

    return Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'status' => Application::STATUS_SUBMITTED,
        'payment_status' => Application::PAYMENT_PAID,
        'application_number' => 'SVNC/'.$program->code.'/'.fake()->unique()->numerify('######'),
        'submitted_at' => $submittedAt ?? now(),
    ]);
}

it('lists all configured reports in the registry', function () {
    $reports = app(ReportRegistry::class)->all();

    expect($reports)->toHaveKey('daily_admission');
    expect($reports)->toHaveKey('application_funnel');
    expect($reports)->toHaveKey('fee_collection');
    expect($reports)->toHaveKey('document_rejections');
    expect($reports)->toHaveKey('withdrawal_refund_register');
    expect($reports)->toHaveKey('gateway_reconciliation');
    expect($reports)->toHaveKey('category_gender_domicile');
    expect($reports)->toHaveKey('merit_cutoff_history');
    expect($reports)->toHaveKey('anti_ragging_compliance');
});

it('groups reports by category for the sidebar', function () {
    $groups = app(ReportRegistry::class)->grouped();

    expect($groups)->toHaveKey('operational');
    expect($groups)->toHaveKey('financial');
    expect($groups)->toHaveKey('compliance');
    expect(count($groups['operational']))->toBe(3);
    expect(count($groups['financial']))->toBe(3);
    expect(count($groups['compliance']))->toBe(3);
});

it('daily admission report rolls up applications by date and programme', function () {
    $session = AcademicSession::factory()->active()->create(['code' => '2026-27']);
    $program = Program::factory()->create(['code' => 'UGCS01']);

    makePaidAppForReport($program, $session, now()->setTime(10, 0));
    makePaidAppForReport($program, $session, now()->setTime(11, 0));
    makePaidAppForReport($program, $session, now()->subDay()->setTime(9, 0));

    $rows = app(DailyAdmissionReport::class)->paginate(['session' => '2026-27']);

    expect($rows->total())->toBe(2);  // 2 distinct dates
    expect(collect($rows->items())->sum('submitted'))->toBe(3);
});

it('application funnel report counts per programme by stage', function () {
    $session = AcademicSession::factory()->active()->create();
    $program = Program::factory()->create(['intake_capacity' => 60]);

    makePaidAppForReport($program, $session);
    Application::factory()->create([
        'student_id' => Student::factory()->create()->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'status' => Application::STATUS_DRAFT,
        'application_number' => 'SVNC/'.$program->code.'/DRAFT',
    ]);

    $rows = app(ApplicationFunnelReport::class)->paginate([]);
    $row = collect($rows->items())->first(fn ($r) => str_contains($r['programme'], $program->code));

    expect($row)->not->toBeNull();
    expect($row['intake'])->toBe(60);
    expect($row['draft'])->toBe(1);
    expect($row['submitted'])->toBe(1);
    expect($row['paid'])->toBe(1);
});

it('fee collection register sums only paid orders', function () {
    $session = AcademicSession::factory()->active()->create();
    $program = Program::factory()->create();
    $app = makePaidAppForReport($program, $session);

    $gateway = PaymentGateway::firstOrCreate(
        ['code' => 'razorpay'],
        ['display_name' => 'Razorpay', 'mode' => 'stub', 'is_active' => true, 'config_encrypted' => encrypt(['key_id' => 'x'])],
    );
    PaymentOrder::create([
        'application_id' => $app->id,
        'payment_gateway_id' => $gateway->id,
        'order_number' => 'PAY/1',
        'purpose' => PaymentOrder::PURPOSE_APPLICATION_FEE,
        'amount' => 500, 'convenience_fee' => 30, 'gst' => 5, 'total' => 535,
        'currency' => 'INR',
        'status' => PaymentOrder::STATUS_PAID,
        'paid_at' => now(),
    ]);
    PaymentOrder::create([
        'application_id' => $app->id,
        'payment_gateway_id' => $gateway->id,
        'order_number' => 'PAY/2',
        'purpose' => PaymentOrder::PURPOSE_APPLICATION_FEE,
        'amount' => 500, 'convenience_fee' => 30, 'gst' => 5, 'total' => 535,
        'currency' => 'INR',
        'status' => PaymentOrder::STATUS_INITIATED,
    ]);

    $rows = app(FeeCollectionRegister::class)->paginate([]);
    expect($rows->total())->toBe(1);

    $first = collect($rows->items())->first();
    expect($first['transactions'])->toBe(1);
    expect((float) $first['total'])->toBe(535.0);
});

it('admin can stream the CSV export', function () {
    $admin = \App\Models\User::create([
        'name' => 'Admin', 'email' => 'rep_admin@svnc.test',
        'mobile' => '99'.fake()->numerify('########'), 'password' => bcrypt('x'), 'status' => 'active',
    ]);
    $admin->assignRole('admin');

    $session = AcademicSession::factory()->active()->create();
    $program = Program::factory()->create();
    makePaidAppForReport($program, $session);

    $this->actingAs($admin)
        ->get(route('admin.reports.export', 'daily_admission'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');
});
