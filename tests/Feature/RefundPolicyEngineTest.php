<?php

use Carbon\Carbon;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\Application;
use Modules\Payments\Models\PaymentGateway;
use Modules\Payments\Models\PaymentOrder;
use Modules\Payments\Models\RefundPolicy;
use Modules\Payments\Services\RefundPolicyEngine;
use Modules\Students\Models\Student;

function makeSessionWithStart(string $startDate): AcademicSession
{
    return AcademicSession::factory()->active()->create([
        'commencement_date' => $startDate,
    ]);
}

function makeGateway9(): PaymentGateway
{
    return PaymentGateway::firstOrCreate(
        ['code' => 'razorpay'],
        ['display_name' => 'Razorpay', 'mode' => 'stub', 'is_active' => true, 'config_encrypted' => encrypt(['key_id' => 'rzp_stub'])],
    );
}

function makePaidApp9(AcademicSession $session, float $feePaid): Application
{
    $program = Program::factory()->create();
    $student = Student::factory()->create();
    $app = Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'status' => Application::STATUS_SUBMITTED,
        'payment_status' => Application::PAYMENT_PAID,
        'application_number' => 'SVNC/'.$program->code.'/'.fake()->unique()->numerify('######'),
    ]);

    PaymentOrder::create([
        'application_id' => $app->id,
        'payment_gateway_id' => makeGateway9()->id,
        'order_number' => 'PAY/'.fake()->unique()->numerify('########'),
        'purpose' => PaymentOrder::PURPOSE_APPLICATION_FEE,
        'amount' => $feePaid,
        'convenience_fee' => 0,
        'gst' => 0,
        'total' => $feePaid,
        'currency' => 'INR',
        'status' => PaymentOrder::STATUS_PAID,
        'paid_at' => now()->subDays(30),
    ]);

    return $app->fresh();
}

function makeUgcPolicy9(AcademicSession $session): RefundPolicy
{
    return RefundPolicy::create([
        'academic_session_id' => $session->id,
        'fee_type' => RefundPolicy::FEE_TYPE_APPLICATION,
        'name' => 'UGC 2026-27',
        'is_active' => true,
        'deduction_cap' => 1000,
        'slabs' => [
            ['from_days' => null, 'to_days' => 30, 'refund_pct' => 100, 'label' => 'more_than_30_days_before'],
            ['from_days' => 30, 'to_days' => 15, 'refund_pct' => 80, 'label' => '15_to_30_days_before'],
            ['from_days' => 15, 'to_days' => 0, 'refund_pct' => 50, 'label' => '0_to_15_days_before'],
            ['from_days' => 0, 'to_days' => null, 'refund_pct' => 0, 'label' => 'on_or_after_session_start'],
        ],
    ]);
}

it('refunds 100% (minus cap) when withdrawn more than 30 days before session start', function () {
    $session = makeSessionWithStart('2026-08-01');
    $app = makePaidApp9($session, 5000);
    makeUgcPolicy9($session);

    $result = app(RefundPolicyEngine::class)->compute($app, Carbon::parse('2026-06-15'));

    expect($result['refund_pct'])->toBe(100.0);
    expect($result['refund_amount'])->toBe(4000.0);
    expect($result['deduction_amount'])->toBe(1000.0);
    expect($result['slab_label'])->toBe('more_than_30_days_before');
});

it('refunds 80% when withdrawn 15-30 days before session start', function () {
    $session = makeSessionWithStart('2026-08-01');
    $app = makePaidApp9($session, 5000);
    makeUgcPolicy9($session);

    $result = app(RefundPolicyEngine::class)->compute($app, Carbon::parse('2026-07-12'));

    expect($result['refund_pct'])->toBe(80.0);
    expect($result['refund_amount'])->toBe(4000.0);
    expect($result['deduction_amount'])->toBe(1000.0);
    expect($result['slab_label'])->toBe('15_to_30_days_before');
});

it('refunds 50% when withdrawn 0-15 days before session start', function () {
    $session = makeSessionWithStart('2026-08-01');
    $app = makePaidApp9($session, 5000);
    makeUgcPolicy9($session);

    $result = app(RefundPolicyEngine::class)->compute($app, Carbon::parse('2026-07-25'));

    expect($result['refund_pct'])->toBe(50.0);
    expect($result['refund_amount'])->toBe(2500.0);
    expect($result['deduction_amount'])->toBe(2500.0);
    expect($result['slab_label'])->toBe('0_to_15_days_before');
});

it('refunds 0% when withdrawn after session start', function () {
    $session = makeSessionWithStart('2026-08-01');
    $app = makePaidApp9($session, 5000);
    makeUgcPolicy9($session);

    $result = app(RefundPolicyEngine::class)->compute($app, Carbon::parse('2026-08-15'));

    expect($result['refund_pct'])->toBe(0.0);
    expect($result['refund_amount'])->toBe(0.0);
    expect($result['deduction_amount'])->toBe(5000.0);
    expect($result['slab_label'])->toBe('on_or_after_session_start');
});

it('returns no_payment when fee was never paid', function () {
    $session = makeSessionWithStart('2026-08-01');
    $program = Program::factory()->create();
    $app = Application::factory()->create([
        'student_id' => Student::factory()->create()->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'status' => Application::STATUS_SUBMITTED,
        'payment_status' => Application::PAYMENT_PENDING,
        'application_number' => 'SVNC/'.$program->code.'/000999',
    ]);
    makeUgcPolicy9($session);

    $result = app(RefundPolicyEngine::class)->compute($app, Carbon::parse('2026-06-15'));

    expect($result['has_payment'])->toBeFalse();
    expect($result['refund_amount'])->toBe(0.0);
    expect($result['slab_label'])->toBe('no_payment');
});

it('returns no_policy when no active refund policy exists for the session', function () {
    $session = makeSessionWithStart('2026-08-01');
    $app = makePaidApp9($session, 5000);

    $result = app(RefundPolicyEngine::class)->compute($app, Carbon::parse('2026-06-15'));

    expect($result['slab_label'])->toBe('no_policy');
    expect($result['refund_amount'])->toBe(0.0);
    expect($result['deduction_amount'])->toBe(5000.0);
});
