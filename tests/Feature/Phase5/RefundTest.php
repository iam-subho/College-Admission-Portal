<?php

use Modules\Academics\Models\Department;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\Application;
use Modules\Payments\Actions\ProcessRefundAction;
use Modules\Payments\Models\PaymentGateway;
use Modules\Payments\Models\PaymentOrder;
use Modules\Payments\Models\PaymentTransaction;
use Modules\Payments\Models\Refund;
use Modules\Students\Models\Student;

it('issues a refund through the original gateway and marks the order refunded', function () {
    $gateway = PaymentGateway::create([
        'code' => 'razorpay',
        'display_name' => 'Razorpay',
        'is_active' => true,
        'mode' => PaymentGateway::MODE_STUB,
        'priority' => 10,
    ]);
    $student = Student::factory()->create(['profile_locked' => true]);
    $dept = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $dept->id]);
    $session = AcademicSession::factory()->active()->create();
    $app = Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
    ]);
    $order = PaymentOrder::create([
        'application_id' => $app->id,
        'payment_gateway_id' => $gateway->id,
        'order_number' => 'SVNC/PAY/2026/RFTEST',
        'purpose' => PaymentOrder::PURPOSE_APPLICATION_FEE,
        'amount' => 500,
        'convenience_fee' => 0,
        'gst' => 0,
        'total' => 500,
        'currency' => 'INR',
        'status' => PaymentOrder::STATUS_PAID,
        'gateway_order_id' => 'order_stub_rf',
        'paid_at' => now(),
    ]);
    $txn = PaymentTransaction::create([
        'payment_order_id' => $order->id,
        'gateway_txn_id' => 'pay_RF_AAA',
        'status' => PaymentTransaction::STATUS_SUCCESS,
        'amount' => 500,
        'method' => 'upi',
        'paid_at' => now(),
    ]);

    $refund = app(ProcessRefundAction::class)->execute(
        order: $order,
        amount: 500,
        reason: 'Withdrawal — pre-session start',
    );

    expect($refund->status)->toBe(Refund::STATUS_COMPLETED);
    expect($refund->gateway_refund_id)->toStartWith('rfnd_stub_');
    expect($refund->payment_transaction_id)->toBe($txn->id);
    expect($order->fresh()->status)->toBe(PaymentOrder::STATUS_REFUNDED);
});

it('returns failure if there is no underlying transaction to refund', function () {
    $gateway = PaymentGateway::create([
        'code' => 'razorpay',
        'display_name' => 'Razorpay',
        'is_active' => true,
        'mode' => PaymentGateway::MODE_STUB,
        'priority' => 10,
    ]);
    $student = Student::factory()->create(['profile_locked' => true]);
    $dept = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $dept->id]);
    $session = AcademicSession::factory()->active()->create();
    $app = Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
    ]);
    $order = PaymentOrder::create([
        'application_id' => $app->id,
        'payment_gateway_id' => $gateway->id,
        'order_number' => 'SVNC/PAY/2026/RFNONE',
        'purpose' => PaymentOrder::PURPOSE_APPLICATION_FEE,
        'amount' => 500,
        'convenience_fee' => 0,
        'gst' => 0,
        'total' => 500,
        'currency' => 'INR',
        'status' => PaymentOrder::STATUS_INITIATED,
        'gateway_order_id' => 'order_stub_rf2',
    ]);

    $refund = app(ProcessRefundAction::class)->execute(
        order: $order,
        amount: 500,
        reason: 'Never paid',
    );

    expect($refund->status)->toBe(Refund::STATUS_FAILED);
    expect($order->fresh()->status)->toBe(PaymentOrder::STATUS_INITIATED);
});
