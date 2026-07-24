<?php

namespace Modules\Payments\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Admissions\Models\Application;
use Modules\Payments\Models\PaymentGateway;
use Modules\Payments\Models\PaymentOrder;
use Modules\Payments\Services\FeeResolver;
use Modules\Payments\Services\PaymentGatewayManager;

class CreatePaymentOrderAction
{
    public function __construct(
        protected FeeResolver $feeResolver,
        protected PaymentGatewayManager $manager,
    ) {}

    /**
     * @return array{order: PaymentOrder, checkout: array<string, mixed>}
     */
    public function execute(Application $application, PaymentGateway $gateway, string $purpose = PaymentOrder::PURPOSE_APPLICATION_FEE): array
    {
        return DB::transaction(function () use ($application, $gateway, $purpose) {
            $fee = $this->feeResolver->applicationFeeFor($application);
            $convenience = $this->feeResolver->convenienceFee($fee['amount'], $gateway->convenience_fee_rule);
            $gst = $this->feeResolver->gstOn($convenience);
            $total = round($fee['amount'] + $convenience + $gst, 2);

            $order = PaymentOrder::create([
                'application_id' => $application->id,
                'payment_gateway_id' => $gateway->id,
                'order_number' => $this->generateOrderNumber(),
                'purpose' => $purpose,
                'amount' => $fee['amount'],
                'convenience_fee' => $convenience,
                'gst' => $gst,
                'total' => $total,
                'currency' => config('payments.currency', 'INR'),
                'status' => PaymentOrder::STATUS_INITIATED,
                'expires_at' => now()->addMinutes((int) config('payments.order_ttl_minutes', 30)),
            ]);

            $checkout = $this->manager->driverFor($gateway)->createOrder($order);

            return ['order' => $order->fresh(), 'checkout' => $checkout['checkout']];
        });
    }

    protected function generateOrderNumber(): string
    {
        $year = now()->format('Y');
        $suffix = strtoupper(Str::random(6));

        return config('admissions.college_code')."/PAY/{$year}/{$suffix}";
    }
}
