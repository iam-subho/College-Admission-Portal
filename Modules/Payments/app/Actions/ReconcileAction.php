<?php

namespace Modules\Payments\Actions;

use Carbon\Carbon;
use Modules\Payments\Models\PaymentGateway;
use Modules\Payments\Models\PaymentOrder;
use Modules\Payments\Models\PaymentTransaction;
use Modules\Payments\Services\PaymentGatewayManager;

class ReconcileAction
{
    public function __construct(protected PaymentGatewayManager $manager) {}

    /**
     * Pull all payments captured at the gateway for the given date and mark
     * any locally-missed orders as paid.
     *
     * @return array{rows_seen: int, orders_paid: int}
     */
    public function execute(PaymentGateway $gateway, Carbon $date): array
    {
        $rows = $this->manager->driverFor($gateway)->reconcile($date);
        $paid = 0;

        foreach ($rows as $row) {
            if (($row['status'] ?? null) !== 'captured') {
                continue;
            }
            if (! ($row['gateway_order_id'] ?? null) || ! ($row['gateway_txn_id'] ?? null)) {
                continue;
            }

            $order = PaymentOrder::where('gateway_order_id', $row['gateway_order_id'])->first();
            if (! $order || $order->status === PaymentOrder::STATUS_PAID) {
                continue;
            }

            PaymentTransaction::firstOrCreate(
                ['gateway_txn_id' => $row['gateway_txn_id']],
                [
                    'payment_order_id' => $order->id,
                    'status' => PaymentTransaction::STATUS_SUCCESS,
                    'amount' => $row['amount'] ?? $order->total,
                    'method' => $row['method'] ?? null,
                    'raw_payload' => $row['raw'] ?? [],
                    'paid_at' => $row['paid_at'] ?? now(),
                ],
            );

            $order->forceFill([
                'status' => PaymentOrder::STATUS_PAID,
                'paid_at' => $row['paid_at'] ?? now(),
            ])->save();

            $paid++;
        }

        return ['rows_seen' => count($rows), 'orders_paid' => $paid];
    }
}
