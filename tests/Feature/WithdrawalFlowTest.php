<?php

use App\Models\User;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\WithdrawalRequest;
use Modules\Payments\Models\PaymentGateway;
use Modules\Payments\Models\PaymentOrder;
use Modules\Payments\Models\Refund;
use Modules\Payments\Models\RefundPolicy;
use Modules\Students\Models\Student;
use Modules\Users\Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeAdmin9b(): User
{
    $u = User::create([
        'name' => 'Admin',
        'email' => fake()->unique()->safeEmail(),
        'mobile' => '99'.fake()->numerify('########'),
        'password' => bcrypt('x'),
        'status' => 'active',
    ]);
    $u->assignRole('admin');

    return $u;
}

function makeWithdrawableApp(AcademicSession $session, float $feePaid = 500): Application
{
    $program = Program::factory()->create();
    $student = Student::factory()->create();
    $student->user->assignRole('student');
    $app = Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'status' => Application::STATUS_SUBMITTED,
        'payment_status' => Application::PAYMENT_PAID,
        'application_number' => 'SVNC/'.$program->code.'/'.fake()->unique()->numerify('######'),
    ]);
    $gateway = PaymentGateway::firstOrCreate(
        ['code' => 'razorpay'],
        ['display_name' => 'Razorpay', 'mode' => 'stub', 'is_active' => true, 'config_encrypted' => encrypt(['key_id' => 'rzp_stub'])],
    );
    PaymentOrder::create([
        'application_id' => $app->id,
        'payment_gateway_id' => $gateway->id,
        'order_number' => 'PAY/'.fake()->unique()->numerify('########'),
        'purpose' => PaymentOrder::PURPOSE_APPLICATION_FEE,
        'amount' => $feePaid, 'convenience_fee' => 0, 'gst' => 0, 'total' => $feePaid,
        'currency' => 'INR', 'status' => PaymentOrder::STATUS_PAID, 'paid_at' => now()->subDays(10),
    ]);

    return $app->fresh();
}

it('student can submit a withdrawal request which stores estimated refund', function () {
    $session = AcademicSession::factory()->active()->create(['commencement_date' => now()->addMonths(2)->toDateString()]);
    $app = makeWithdrawableApp($session, 1000);
    RefundPolicy::create([
        'academic_session_id' => $session->id,
        'fee_type' => RefundPolicy::FEE_TYPE_APPLICATION,
        'name' => 'Test Policy',
        'is_active' => true,
        'deduction_cap' => null,
        'slabs' => [
            ['from_days' => null, 'to_days' => 30, 'refund_pct' => 100, 'label' => '>30d'],
            ['from_days' => 30, 'to_days' => 0, 'refund_pct' => 50, 'label' => '0-30d'],
            ['from_days' => 0, 'to_days' => null, 'refund_pct' => 0, 'label' => 'post-start'],
        ],
    ]);

    $this->actingAs($app->student->user)
        ->post("/student/applications/{$app->id}/withdraw", ['reason' => 'Joining another college that issued admit card earlier'])
        ->assertRedirect();

    $w = WithdrawalRequest::where('application_id', $app->id)->first();
    expect($w)->not->toBeNull();
    expect($w->status)->toBe('pending');
    expect((float) $w->estimated_refund)->toBe(1000.0);
    expect((float) $w->estimated_deduction)->toBe(0.0);
});

it('admin approves: application becomes withdrawn and a pending refund is created', function () {
    $session = AcademicSession::factory()->active()->create(['commencement_date' => now()->addMonths(2)->toDateString()]);
    $app = makeWithdrawableApp($session, 1000);
    RefundPolicy::create([
        'academic_session_id' => $session->id,
        'fee_type' => RefundPolicy::FEE_TYPE_APPLICATION,
        'name' => 'Test', 'is_active' => true, 'deduction_cap' => null,
        'slabs' => [['from_days' => null, 'to_days' => null, 'refund_pct' => 100, 'label' => 'always_100']],
    ]);

    $this->actingAs($app->student->user)
        ->post("/student/applications/{$app->id}/withdraw", ['reason' => 'Personal reasons explained in full']);

    $w = WithdrawalRequest::where('application_id', $app->id)->firstOrFail();

    $this->actingAs(makeAdmin9b())
        ->post("/admin/withdrawals/{$w->id}/approve", ['admin_remark' => 'Approved within window'])
        ->assertRedirect();

    expect($app->fresh()->status)->toBe(Application::STATUS_WITHDRAWN);
    expect($w->fresh()->status)->toBe('approved');

    $refund = Refund::where('application_id', $app->id)->first();
    expect($refund)->not->toBeNull();
    expect((float) $refund->amount)->toBe(1000.0);
    expect($refund->status)->toBe(Refund::STATUS_PENDING);
    expect($refund->refund_method)->toBe(Refund::METHOD_OFFLINE);
    expect($refund->withdrawal_request_id)->toBe($w->id);
});

it('approving without payment marks app withdrawn but creates no refund', function () {
    $session = AcademicSession::factory()->active()->create(['commencement_date' => now()->addMonths(2)->toDateString()]);
    $program = Program::factory()->create();
    $student = Student::factory()->create();
    $student->user->assignRole('student');
    $app = Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'status' => Application::STATUS_SUBMITTED,
        'payment_status' => Application::PAYMENT_PENDING,
        'application_number' => 'SVNC/UG/2026/UNPAID',
    ]);

    $this->actingAs($app->student->user)
        ->post("/student/applications/{$app->id}/withdraw", ['reason' => 'Changed my mind about applying']);
    $w = WithdrawalRequest::where('application_id', $app->id)->firstOrFail();

    $this->actingAs(makeAdmin9b())
        ->post("/admin/withdrawals/{$w->id}/approve", ['admin_remark' => 'No fee paid']);

    expect($app->fresh()->status)->toBe(Application::STATUS_WITHDRAWN);
    expect(Refund::where('application_id', $app->id)->count())->toBe(0);
});

it('admin can mark a refund as paid offline with UTR', function () {
    $session = AcademicSession::factory()->active()->create(['commencement_date' => now()->addMonths(2)->toDateString()]);
    $app = makeWithdrawableApp($session, 1000);
    RefundPolicy::create([
        'academic_session_id' => $session->id,
        'fee_type' => RefundPolicy::FEE_TYPE_APPLICATION,
        'name' => 'P', 'is_active' => true, 'deduction_cap' => null,
        'slabs' => [['from_days' => null, 'to_days' => null, 'refund_pct' => 100, 'label' => 'always']],
    ]);

    $this->actingAs($app->student->user)
        ->post("/student/applications/{$app->id}/withdraw", ['reason' => 'Joining IIT instead']);
    $w = WithdrawalRequest::where('application_id', $app->id)->firstOrFail();
    $admin = makeAdmin9b();
    $this->actingAs($admin)->post("/admin/withdrawals/{$w->id}/approve", ['admin_remark' => null]);

    $refund = Refund::where('application_id', $app->id)->firstOrFail();
    $this->actingAs($admin)
        ->post("/admin/refunds/{$refund->id}/mark-paid", ['offline_reference' => 'NEFT-2026-05-28-99887766'])
        ->assertRedirect();

    $refund->refresh();
    expect($refund->status)->toBe(Refund::STATUS_COMPLETED);
    expect($refund->offline_reference)->toBe('NEFT-2026-05-28-99887766');
    expect($refund->completed_at)->not->toBeNull();
});

it('rejects withdrawal of a draft application', function () {
    $session = AcademicSession::factory()->active()->create();
    $program = Program::factory()->create();
    $student = Student::factory()->create();
    $student->user->assignRole('student');
    $app = Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'status' => Application::STATUS_DRAFT,
        'application_number' => 'SVNC/UG/2026/DRAFT',
    ]);

    $this->actingAs($app->student->user)
        ->post("/student/applications/{$app->id}/withdraw", ['reason' => 'Trying to withdraw a draft application'])
        ->assertRedirect();

    expect(WithdrawalRequest::count())->toBe(0);
});
