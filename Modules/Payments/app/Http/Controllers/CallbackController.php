<?php

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Admissions\Models\Application;
use Modules\Payments\Models\PaymentGateway;
use Modules\Payments\Models\PaymentOrder;
use Modules\Payments\Models\PaymentTransaction;
use Modules\Payments\Services\Gateways\RazorpayDriver;

/**
 * Gateway-originated client-side callbacks. Triggered from the gateway
 * checkout JS handler — e.g. Razorpay's `handler` fires this with
 * razorpay_payment_id / razorpay_order_id / razorpay_signature.
 *
 * Signature is verified here so we can mark the order paid synchronously
 * (so the student sees "Paid" without waiting for the webhook). The
 * webhook continues to be authoritative — duplicates are idempotent
 * because PaymentTransaction is keyed on gateway_txn_id.
 */
class CallbackController extends Controller
{
    public function razorpay(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'razorpay_payment_id' => ['required', 'string'],
            'razorpay_order_id' => ['required', 'string'],
            'razorpay_signature' => ['required', 'string'],
        ]);

        $order = PaymentOrder::where('gateway_order_id', $data['razorpay_order_id'])->firstOrFail();
        $gateway = PaymentGateway::where('code', 'razorpay')->firstOrFail();

        $driver = new RazorpayDriver($gateway);
        $valid = $driver->verifyCheckoutSignature(
            $data['razorpay_order_id'],
            $data['razorpay_payment_id'],
            $data['razorpay_signature'],
        );

        if (! $valid) {
            return redirect()
                ->route('student.payments.show', $order->application_id)
                ->with('flash', ['error' => 'Payment signature verification failed. If you were charged, our webhook will reconcile shortly.']);
        }

        // Mark txn + order + application paid. firstOrCreate keeps this safe
        // if the webhook beat us to it.
        PaymentTransaction::firstOrCreate(
            ['gateway_txn_id' => $data['razorpay_payment_id']],
            [
                'payment_order_id' => $order->id,
                'status' => PaymentTransaction::STATUS_SUCCESS,
                'amount' => $order->total,
                'method' => null,
                'raw_payload' => $data,
                'paid_at' => now(),
            ],
        );

        if ($order->status !== PaymentOrder::STATUS_PAID) {
            $order->forceFill([
                'status' => PaymentOrder::STATUS_PAID,
                'paid_at' => now(),
            ])->save();
        }

        // Side effects depend on what this order paid for.
        $allocation = null;
        if ($order->purpose === PaymentOrder::PURPOSE_APPLICATION_FEE) {
            $order->application?->forceFill([
                'payment_status' => Application::PAYMENT_PAID,
            ])->save();
        } elseif ($order->purpose === PaymentOrder::PURPOSE_ADMISSION_FEE) {
            $allocation = \Modules\Seats\Models\SeatAllocation::where('admission_fee_order_id', $order->id)->first();
            if ($allocation && $allocation->status !== \Modules\Seats\Models\SeatAllocation::STATUS_ADMITTED) {
                $allocation->forceFill([
                    'status' => \Modules\Seats\Models\SeatAllocation::STATUS_ADMITTED,
                    'admitted_at' => now(),
                ])->save();
            }
        }

        $route = $allocation
            ? ['student.allotment.show', $order->application_id]
            : ['student.payments.show', $order->application_id];

        return redirect()->route(...$route)
            ->with('flash', ['success' => 'Payment received. Your admission is now confirmed.']);
    }
}
