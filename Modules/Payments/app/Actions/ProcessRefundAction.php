<?php

namespace Modules\Payments\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Payments\Models\PaymentOrder;
use Modules\Payments\Models\Refund;
use Modules\Payments\Services\PaymentGatewayManager;

class ProcessRefundAction
{
    public function __construct(protected PaymentGatewayManager $manager) {}

    public function execute(
        PaymentOrder $order,
        float $amount,
        ?int $userId = null,
        ?string $reason = null,
        float $deduction = 0,
        ?string $policySlab = null,
    ): Refund {
        return DB::transaction(function () use ($order, $amount, $userId, $reason, $deduction, $policySlab) {
            $transaction = $order->transactions()
                ->where('status', 'success')
                ->latest('paid_at')
                ->first();

            $refund = Refund::create([
                'payment_order_id' => $order->id,
                'payment_transaction_id' => $transaction?->id,
                'amount' => $amount,
                'deduction_amount' => $deduction,
                'policy_slab' => $policySlab,
                'status' => Refund::STATUS_INITIATED,
                'initiated_by' => $userId,
                'reason' => $reason,
            ]);

            $result = $this->manager->driverFor($order->gateway)->refund($refund);

            $refund->forceFill([
                'status' => $result->status,
                'gateway_refund_id' => $result->gatewayRefundId,
                'gateway_payload' => $result->rawPayload,
                'completed_at' => $result->status === Refund::STATUS_COMPLETED ? now() : null,
            ])->save();

            if ($result->status === Refund::STATUS_COMPLETED) {
                $order->forceFill(['status' => PaymentOrder::STATUS_REFUNDED])->save();
            }

            return $refund->fresh();
        });
    }
}
