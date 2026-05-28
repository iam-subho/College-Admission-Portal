<?php

use Modules\Academics\Models\Department;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\Application;
use Modules\Payments\Actions\ReconcileAction;
use Modules\Payments\Models\PaymentGateway;
use Modules\Payments\Models\PaymentOrder;
use Modules\Payments\Models\PaymentTransaction;
use Modules\Payments\Services\PaymentGatewayManager;
use Modules\Students\Models\Student;

it('reconcile picks up an order that was paid at the gateway but never webhooked', function () {
    $gateway = PaymentGateway::create([
        'code' => 'razorpay',
        'display_name' => 'Razorpay',
        'is_active' => true,
        'mode' => PaymentGateway::MODE_TEST,
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
        'order_number' => 'SVNC/PAY/2026/RC1',
        'purpose' => PaymentOrder::PURPOSE_APPLICATION_FEE,
        'amount' => 500,
        'convenience_fee' => 0,
        'gst' => 0,
        'total' => 500,
        'currency' => 'INR',
        'status' => PaymentOrder::STATUS_PROCESSING,
        'gateway_order_id' => 'order_missing_001',
    ]);

    // Mock the manager to return a driver that yields one captured payment
    // matching $order's gateway_order_id.
    $stubDriver = new class implements \Modules\Payments\Contracts\PaymentGatewayContract
    {
        public function code(): string
        {
            return 'razorpay';
        }

        public function createOrder($order): array
        {
            return [];
        }

        public function verifyWebhook($r): \Modules\Payments\Contracts\WebhookResult
        {
            return \Modules\Payments\Contracts\WebhookResult::invalid('n/a');
        }

        public function refund($r): \Modules\Payments\Contracts\RefundResult
        {
            return \Modules\Payments\Contracts\RefundResult::failure('n/a');
        }

        public function reconcile(\Carbon\Carbon $date): array
        {
            return [[
                'gateway_order_id' => 'order_missing_001',
                'gateway_txn_id' => 'pay_recon_1',
                'amount' => 500,
                'status' => 'captured',
                'method' => 'card',
                'paid_at' => now(),
                'raw' => ['fake' => true],
            ]];
        }
    };

    $this->mock(PaymentGatewayManager::class, function ($mock) use ($stubDriver) {
        $mock->shouldReceive('driverFor')->andReturn($stubDriver);
    });

    $result = app(ReconcileAction::class)->execute($gateway, now());

    expect($result)->toMatchArray(['rows_seen' => 1, 'orders_paid' => 1]);
    expect($order->fresh()->status)->toBe(PaymentOrder::STATUS_PAID);
    expect(PaymentTransaction::where('gateway_txn_id', 'pay_recon_1')->exists())->toBeTrue();
});
