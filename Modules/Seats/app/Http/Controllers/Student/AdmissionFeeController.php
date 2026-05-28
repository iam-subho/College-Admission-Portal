<?php

namespace Modules\Seats\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Payments\Models\PaymentGateway;
use Modules\Payments\Models\PaymentOrder;
use Modules\Payments\Services\AdmissionFeeResolver;
use Modules\Payments\Services\PaymentGatewayManager;
use Modules\Seats\Models\SeatAllocation;

/**
 * Admission fee payment flow. Reuses Phase 5's PaymentOrder + gateway driver
 * pipeline; the only difference vs. application fee is purpose = 'admission_fee'.
 *
 * When the payment captures (webhook OR client-side callback), the
 * SeatAllocation it references is bumped to 'admitted'.
 */
class AdmissionFeeController extends Controller
{
    public function __construct(
        protected PaymentGatewayManager $manager,
        protected AdmissionFeeResolver $feeResolver,
    ) {}

    public function show(Request $request, SeatAllocation $allocation): Response|RedirectResponse
    {
        $this->authorize($allocation, $request);

        $allocation->load([
            // NOTE: include `admission_fee` in the column projection — the resolver reads it.
            'application.program:id,code,name,admission_fee',
            'application.session:id,code',
            'application.student:id,user_id,reservation_category_id',
            'admissionFeeOrder',
        ]);
        $fee = $this->feeResolver->admissionFeeFor($allocation->application);
        $gateways = $this->manager->activeGateways();
        $user = $request->user();

        $latestOrder = PaymentOrder::query()
            ->where('application_id', $allocation->application_id)
            ->where('purpose', PaymentOrder::PURPOSE_ADMISSION_FEE)
            ->latest('id')->first();

        $resumeCheckout = null;
        if ($latestOrder && in_array($latestOrder->status, [PaymentOrder::STATUS_INITIATED, PaymentOrder::STATUS_PROCESSING], true)) {
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
                'description' => 'Admission Fee — '.$latestOrder->order_number,
                'callback_url' => route('payments.callback.razorpay'),
            ];
        }

        return Inertia::render('Student/AdmissionFee', [
            'allocation' => $allocation,
            'fee' => $fee,
            'gst_percent' => (float) config('payments.gst_percent', 18),
            'gateways' => $gateways->map(fn (PaymentGateway $g) => [
                'id' => $g->id,
                'code' => $g->code,
                'display_name' => $g->display_name,
                'mode' => $g->mode,
                'logo_url' => $g->logo_url,
                'convenience_fee' => $this->feeResolver instanceof \Modules\Payments\Services\FeeResolver
                    ? $this->feeResolver->convenienceFee($fee['amount'], $g->convenience_fee_rule)
                    : 0,
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

    public function init(Request $request, SeatAllocation $allocation): RedirectResponse
    {
        $this->authorize($allocation, $request);

        if ($allocation->status !== SeatAllocation::STATUS_ACCEPTED) {
            return back()->with('flash', ['error' => 'Accept the seat before paying the admission fee.']);
        }

        $data = $request->validate([
            'gateway_id' => ['required', 'exists:payment_gateways,id'],
        ]);

        $gateway = PaymentGateway::where('id', $data['gateway_id'])->where('is_active', true)->firstOrFail();
        $fee = $this->feeResolver->admissionFeeFor($allocation->application);

        $convenience = 0;
        if (method_exists($this->feeResolver, 'convenienceFee')) {
            $convenience = $this->feeResolver->convenienceFee($fee['amount'], $gateway->convenience_fee_rule);
        } else {
            $convenience = app(\Modules\Payments\Services\FeeResolver::class)->convenienceFee($fee['amount'], $gateway->convenience_fee_rule);
        }
        $gst = round($convenience * ((float) config('payments.gst_percent', 18)) / 100, 2);
        $total = round($fee['amount'] + $convenience + $gst, 2);

        $order = DB::transaction(function () use ($allocation, $gateway, $fee, $convenience, $gst, $total) {
            $order = PaymentOrder::create([
                'application_id' => $allocation->application_id,
                'payment_gateway_id' => $gateway->id,
                'order_number' => 'SVNC/ADM/'.now()->format('Y').'/'.strtoupper(Str::random(6)),
                'purpose' => PaymentOrder::PURPOSE_ADMISSION_FEE,
                'amount' => $fee['amount'],
                'convenience_fee' => $convenience,
                'gst' => $gst,
                'total' => $total,
                'currency' => config('payments.currency', 'INR'),
                'status' => PaymentOrder::STATUS_INITIATED,
                'expires_at' => now()->addMinutes((int) config('payments.order_ttl_minutes', 30)),
            ]);

            $allocation->forceFill(['admission_fee_order_id' => $order->id])->save();

            return $order;
        });

        $checkout = $this->manager->driverFor($gateway)->createOrder($order);

        return back()->with('flash', [
            'success' => "Admission-fee order {$order->order_number} created.",
            'checkout' => $checkout['checkout'],
        ]);
    }

    protected function authorize(SeatAllocation $allocation, Request $request): void
    {
        $app = $allocation->application ?? $allocation->load('application.student')->application;
        abort_unless(
            $app?->student?->user_id === $request->user()->id,
            403,
            'Not your allocation.',
        );
    }
}
