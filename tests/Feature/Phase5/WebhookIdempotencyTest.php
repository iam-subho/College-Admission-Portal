<?php

use Illuminate\Http\Request;
use Modules\Academics\Models\Department;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\Application;
use Modules\Payments\Actions\ProcessWebhookAction;
use Modules\Payments\Models\PaymentGateway;
use Modules\Payments\Models\PaymentOrder;
use Modules\Payments\Models\PaymentTransaction;
use Modules\Payments\Models\PaymentWebhook;
use Modules\Students\Models\Student;

function makeOrder(): PaymentOrder
{
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

    return PaymentOrder::create([
        'application_id' => $app->id,
        'payment_gateway_id' => $gateway->id,
        'order_number' => 'SVNC/PAY/2026/AAAAAA',
        'purpose' => PaymentOrder::PURPOSE_APPLICATION_FEE,
        'amount' => 500,
        'convenience_fee' => 30,
        'gst' => 5.40,
        'total' => 535.40,
        'currency' => 'INR',
        'status' => PaymentOrder::STATUS_PROCESSING,
        'gateway_order_id' => 'order_stub_abc',
    ]);
}

function makeWebhookRequest(string $txnId, string $gatewayOrderId, int $amountPaise): Request
{
    $payload = [
        'event' => 'payment.captured',
        'payload' => [
            'payment' => [
                'entity' => [
                    'id' => $txnId,
                    'order_id' => $gatewayOrderId,
                    'amount' => $amountPaise,
                    'currency' => 'INR',
                    'status' => 'captured',
                    'method' => 'upi',
                ],
            ],
        ],
    ];
    $request = Request::create('/webhooks/razorpay', 'POST', content: json_encode($payload));
    $request->headers->set('Content-Type', 'application/json');

    return $request;
}

it('marks the order paid on the first captured webhook', function () {
    $order = makeOrder();
    $request = makeWebhookRequest('pay_AAAA', $order->gateway_order_id, 53540);

    app(ProcessWebhookAction::class)->execute('razorpay', $request);

    expect($order->fresh()->status)->toBe(PaymentOrder::STATUS_PAID);
    expect(PaymentTransaction::count())->toBe(1);
});

it('is idempotent — duplicate webhooks do not double-record the transaction', function () {
    $order = makeOrder();
    $request = makeWebhookRequest('pay_BBBB', $order->gateway_order_id, 53540);

    app(ProcessWebhookAction::class)->execute('razorpay', $request);
    app(ProcessWebhookAction::class)->execute('razorpay', $request);
    app(ProcessWebhookAction::class)->execute('razorpay', $request);

    expect(PaymentWebhook::count())->toBe(1);
    expect(PaymentTransaction::count())->toBe(1);
    expect($order->fresh()->status)->toBe(PaymentOrder::STATUS_PAID);
});

it('records a failed payment without marking the order paid', function () {
    $order = makeOrder();
    $payload = [
        'event' => 'payment.failed',
        'payload' => [
            'payment' => [
                'entity' => [
                    'id' => 'pay_FAIL',
                    'order_id' => $order->gateway_order_id,
                    'amount' => 53540,
                    'currency' => 'INR',
                    'status' => 'failed',
                ],
            ],
        ],
    ];
    $request = Request::create('/webhooks/razorpay', 'POST', content: json_encode($payload));
    $request->headers->set('Content-Type', 'application/json');

    app(ProcessWebhookAction::class)->execute('razorpay', $request);

    expect($order->fresh()->status)->toBe(PaymentOrder::STATUS_FAILED);
    expect(PaymentTransaction::count())->toBe(0);
});
