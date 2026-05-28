<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Modules\Academics\Models\Department;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;
use Modules\Admissions\Models\Application;
use Modules\Payments\Models\PaymentGateway;
use Modules\Payments\Models\PaymentOrder;
use Modules\Students\Models\Student;
use Modules\Users\Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);

    PaymentGateway::create([
        'code' => 'razorpay',
        'display_name' => 'Razorpay',
        'is_active' => true,
        'mode' => PaymentGateway::MODE_STUB,
        'priority' => 10,
        'convenience_fee_rule' => 'flat:30',
    ]);
});

function makeStudentForPayment(): array
{
    $user = User::create([
        'name' => 'Payee Student',
        'email' => 'payee@svnc.test',
        'mobile' => '9988770211',
        'password' => Hash::make('Secret123'),
        'status' => 'active',
    ]);
    $user->assignRole('student');
    $student = Student::create(['user_id' => $user->id, 'profile_locked' => true]);

    $dept = Department::factory()->create();
    $program = Program::factory()->create(['department_id' => $dept->id]);
    $session = AcademicSession::factory()->active()->create();
    $app = Application::factory()->create([
        'student_id' => $student->id,
        'program_id' => $program->id,
        'academic_session_id' => $session->id,
        'status' => Application::STATUS_SUBMITTED,
        'application_number' => 'SVNC/UG/2026/000099',
    ]);

    return [$user, $app];
}

it('renders the payment page with active gateways and the fee summary', function () {
    [$user, $app] = makeStudentForPayment();

    $this->actingAs($user)
        ->get("/student/applications/{$app->id}/payment")
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Student/Payment')
            ->has('gateways', 1)
            ->where('gateways.0.code', 'razorpay')
            ->has('fee')
        );
});

it('initialises a payment order and creates the gateway order id', function () {
    [$user, $app] = makeStudentForPayment();
    $gateway = PaymentGateway::where('code', 'razorpay')->first();

    $this->actingAs($user)
        ->post("/student/applications/{$app->id}/payment/init", ['gateway_id' => $gateway->id])
        ->assertRedirect();

    $order = PaymentOrder::where('application_id', $app->id)->first();
    expect($order)->not->toBeNull();
    expect($order->gateway_order_id)->toStartWith('order_stub_');
    expect($order->total)->toEqual('535.40'); // 500 + 30 + 18% of 30 = 535.40
});

it('mock-pay flow marks the order paid via the webhook pipeline', function () {
    [$user, $app] = makeStudentForPayment();
    $gateway = PaymentGateway::where('code', 'razorpay')->first();

    $this->actingAs($user)
        ->post("/student/applications/{$app->id}/payment/init", ['gateway_id' => $gateway->id]);

    $order = PaymentOrder::where('application_id', $app->id)->first();

    $this->actingAs($user)
        ->post("/student/payments/{$order->id}/mock-pay")
        ->assertRedirect();

    expect($order->fresh()->status)->toBe(PaymentOrder::STATUS_PAID);
});
