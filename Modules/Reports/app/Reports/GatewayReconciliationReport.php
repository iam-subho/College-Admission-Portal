<?php

namespace Modules\Reports\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Payments\Models\PaymentOrder;
use Modules\Reports\Services\ReportCache;

/**
 * Per (gateway, date) — counts of orders by status, paid totals, and a
 * discrepancy row showing orders without any transaction record. Used
 * during daily gateway reconciliation against the gateway dashboard.
 */
class GatewayReconciliationReport extends BaseReport
{
    public function key(): string
    {
        return 'gateway_reconciliation';
    }

    public function title(): string
    {
        return 'Gateway Reconciliation';
    }

    public function group(): string
    {
        return 'financial';
    }

    public function tags(): array
    {
        return [ReportCache::TAG_PAYMENTS];
    }

    public function filterSchema(): array
    {
        return [
            ['key' => 'from', 'label' => 'From Date', 'type' => 'date'],
            ['key' => 'to', 'label' => 'To Date', 'type' => 'date'],
            ['key' => 'gateway', 'label' => 'Gateway Code', 'type' => 'text'],
        ];
    }

    public function columns(): array
    {
        return [
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'gateway', 'label' => 'Gateway'],
            ['key' => 'initiated', 'label' => 'Initiated', 'num' => true],
            ['key' => 'processing', 'label' => 'Processing', 'num' => true],
            ['key' => 'paid', 'label' => 'Paid', 'num' => true],
            ['key' => 'failed', 'label' => 'Failed', 'num' => true],
            ['key' => 'expired', 'label' => 'Expired', 'num' => true],
            ['key' => 'paid_total', 'label' => 'Paid Total (₹)', 'num' => true],
            ['key' => 'orphans', 'label' => 'No-Txn Orphans', 'num' => true],
        ];
    }

    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return $this->cache->remember(
            'gateway_recon:'.md5(json_encode($filters).':p'.$perPage.':page'.request()->input('page', 1)),
            $this->tags(),
            300,
            fn () => $this->build($filters)->paginate($perPage)->withQueryString(),
        );
    }

    public function all(array $filters): iterable
    {
        return $this->build($filters)->get();
    }

    protected function build(array $filters)
    {
        $statuses = [
            PaymentOrder::STATUS_INITIATED,
            PaymentOrder::STATUS_PROCESSING,
            PaymentOrder::STATUS_PAID,
            PaymentOrder::STATUS_FAILED,
            PaymentOrder::STATUS_EXPIRED,
        ];

        $query = PaymentOrder::query()
            ->selectRaw('DATE(payment_orders.created_at) as date')
            ->selectRaw('payment_gateways.code as gateway')
            ->selectRaw('SUM(CASE WHEN payment_orders.status = ? THEN 1 ELSE 0 END) as initiated', [PaymentOrder::STATUS_INITIATED])
            ->selectRaw('SUM(CASE WHEN payment_orders.status = ? THEN 1 ELSE 0 END) as processing', [PaymentOrder::STATUS_PROCESSING])
            ->selectRaw('SUM(CASE WHEN payment_orders.status = ? THEN 1 ELSE 0 END) as paid', [PaymentOrder::STATUS_PAID])
            ->selectRaw('SUM(CASE WHEN payment_orders.status = ? THEN 1 ELSE 0 END) as failed', [PaymentOrder::STATUS_FAILED])
            ->selectRaw('SUM(CASE WHEN payment_orders.status = ? THEN 1 ELSE 0 END) as expired', [PaymentOrder::STATUS_EXPIRED])
            ->selectRaw('SUM(CASE WHEN payment_orders.status = ? THEN payment_orders.total ELSE 0 END) as paid_total', [PaymentOrder::STATUS_PAID])
            ->selectRaw('SUM(CASE WHEN payment_orders.status = ? AND NOT EXISTS (SELECT 1 FROM payment_transactions WHERE payment_transactions.payment_order_id = payment_orders.id) THEN 1 ELSE 0 END) as orphans',
                [PaymentOrder::STATUS_PAID])
            ->join('payment_gateways', 'payment_gateways.id', '=', 'payment_orders.payment_gateway_id')
            ->whereIn('payment_orders.status', $statuses)
            ->groupBy('date', 'payment_gateways.code')
            ->orderByDesc('date');

        if (! empty($filters['from'])) {
            $query->whereDate('payment_orders.created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('payment_orders.created_at', '<=', $filters['to']);
        }
        if (! empty($filters['gateway'])) {
            $query->where('payment_gateways.code', $filters['gateway']);
        }

        return $query;
    }
}
