<?php

namespace Modules\Payments\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Admissions\Models\Application;
use Modules\Payments\Actions\CreatePaymentOrderAction;
use Modules\Payments\Models\PaymentGateway;
use Modules\Payments\Models\PaymentOrder;
use Modules\Payments\Services\FeeResolver;
use Modules\Payments\Services\PaymentGatewayManager;

class PaymentController extends Controller
{
    public function __construct(
        protected PaymentGatewayManager $manager,
        protected FeeResolver $feeResolver,
    ) {}

    public function show(Request $request, Application $application): Response|RedirectResponse
    {
        $this->authorizeOwnership($application, $request);

        $application->load(['program:id,code,name,type', 'session:id,code,payment_mode,application_fee', 'student:id,reservation_category_id,user_id']);

        // One-time mode: if this student already paid in this session
        // (via a prior application), mark this app as covered and skip
        // the payment screen entirely.
        if ($application->session?->payment_mode === \Modules\Admissions\Models\AcademicSession::PAYMENT_MODE_ONE_TIME
            && ! $application->covered_by_payment_order_id
            && ! $application->paymentOrders()->where('status', \Modules\Payments\Models\PaymentOrder::STATUS_PAID)->exists()) {

            $covering = $this->feeResolver->findCoveringOrder($application);
            if ($covering) {
                $application->forceFill([
                    'covered_by_payment_order_id' => $covering->id,
                    'payment_status' => \Modules\Admissions\Models\Application::PAYMENT_COVERED,
                ])->save();

                return redirect()->route('student.applications.index')->with('flash', [
                    'success' => "Application {$application->application_number} is covered by your earlier payment (Order {$covering->order_number}).",
                ]);
            }
        }

        $latestOrder = $application->paymentOrders()->latest('id')->first();

        $fee = $this->feeResolver->applicationFeeFor($application);
        $gateways = $this->manager->activeGateways();

        $user = $request->user();

        // If the latest order is still in-flight (initiated / processing),
        // build a checkout payload from it so the Vue can re-open the
        // Razorpay modal without creating a duplicate order.
        $resumeCheckout = null;
        if ($latestOrder
            && in_array($latestOrder->status, [PaymentOrder::STATUS_INITIATED, PaymentOrder::STATUS_PROCESSING], true)
            && $latestOrder->gateway_order_id) {
            $gw = $latestOrder->gateway;
            $resumeCheckout = [
                'gateway_code' => $gw->code,
                'mode' => $gw->mode,
                'key' => $gw->config['key_id'] ?? null,
                'gateway_order_id' => $latestOrder->gateway_order_id,
                'internal_order_id' => $latestOrder->id,
                'amount_paise' => (int) round($latestOrder->total * 100),
                'currency' => $latestOrder->currency,
                'name' => 'SVNC Admissions',
                'description' => 'Application Fee — '.$latestOrder->order_number,
                'callback_url' => route('payments.callback.razorpay'),
            ];
        }

        return Inertia::render('Student/Payment', [
            'application' => $application,
            'fee' => $fee,
            'gst_percent' => (float) config('payments.gst_percent', 18),
            'gateways' => $gateways->map(fn (PaymentGateway $g) => [
                'id' => $g->id,
                'code' => $g->code,
                'display_name' => $g->display_name,
                'mode' => $g->mode,
                'logo_url' => $g->logo_url,
                'convenience_fee' => $this->feeResolver->convenienceFee($fee['amount'], $g->convenience_fee_rule),
            ]),
            'order' => $latestOrder,
            'resume_checkout' => $resumeCheckout,
            'prefill' => [
                'name' => $user->name,
                'email' => $user->email,
                'contact' => $user->mobile,
            ],
        ]);
    }

    public function init(Request $request, Application $application, CreatePaymentOrderAction $action): RedirectResponse
    {
        $this->authorizeOwnership($application, $request);

        $data = $request->validate([
            'gateway_id' => ['required', 'exists:payment_gateways,id'],
        ]);

        $gateway = PaymentGateway::where('id', $data['gateway_id'])->where('is_active', true)->firstOrFail();
        $result = $action->execute($application, $gateway);

        app(\Modules\Audit\Services\DpdpConsentRecorder::class)->record(
            scope: \Modules\Users\Models\DpdpConsent::SCOPE_PAYMENT,
            userId: $request->user()->id,
            request: $request,
            metadata: [
                'application_id' => $application->id,
                'application_number' => $application->application_number,
                'order_number' => $result['order']->order_number,
                'gateway' => $gateway->provider,
            ],
        );

        return back()->with('flash', [
            'success' => "Order {$result['order']->order_number} created. Complete payment to confirm.",
            'checkout' => $result['checkout'],
        ]);
    }

    /**
     * Stub-mode mock pay endpoint. Generates a synthetic Razorpay-shaped
     * webhook event and feeds it through the normal webhook pipeline so the
     * full code path (signature, persistence, idempotency) is exercised.
     */
    public function mockPay(Request $request, PaymentOrder $order): RedirectResponse
    {
        abort_unless($order->application->student->user_id === $request->user()->id, 403);
        abort_unless($order->gateway->isStub(), 403, 'Mock pay is stub-mode only.');

        $payload = [
            'event' => 'payment.captured',
            'payload' => [
                'payment' => [
                    'entity' => [
                        'id' => 'pay_stub_'.\Illuminate\Support\Str::random(12),
                        'order_id' => $order->gateway_order_id,
                        'amount' => (int) round($order->total * 100),
                        'currency' => $order->currency,
                        'status' => 'captured',
                        'method' => 'upi',
                    ],
                ],
            ],
        ];

        $faked = Request::create(
            uri: route('webhooks.gateway', ['gateway' => $order->gateway->code]),
            method: 'POST',
            content: json_encode($payload),
        );
        $faked->headers->set('Content-Type', 'application/json');

        app(\Modules\Payments\Actions\ProcessWebhookAction::class)->execute($order->gateway->code, $faked);

        return redirect()->route('student.payments.show', $order->application)
            ->with('flash', ['success' => 'Payment received (stub).']);
    }

    protected function authorizeOwnership(Application $application, Request $request): void
    {
        abort_unless(
            $application->student?->user_id === $request->user()->id,
            403,
            'Not your application.',
        );
    }
}
