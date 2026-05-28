<?php

namespace Modules\Reports\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Reports\Services\ReportCache;

/**
 * Per (date, gateway, purpose, programme) — count + total collected.
 * Used as the daily fee-collection register for college accounts.
 */
class FeeCollectionRegister extends BaseReport
{
    public function key(): string
    {
        return 'fee_collection';
    }

    public function title(): string
    {
        return 'Fee Collection Register';
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
            ['key' => 'purpose', 'label' => 'Purpose', 'type' => 'text'],
        ];
    }

    public function columns(): array
    {
        return [
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'gateway', 'label' => 'Gateway'],
            ['key' => 'purpose', 'label' => 'Purpose'],
            ['key' => 'programme', 'label' => 'Programme'],
            ['key' => 'transactions', 'label' => 'Txns', 'num' => true],
            ['key' => 'amount', 'label' => 'Fee Amount (₹)', 'num' => true],
            ['key' => 'convenience', 'label' => 'Convenience (₹)', 'num' => true],
            ['key' => 'gst', 'label' => 'GST (₹)', 'num' => true],
            ['key' => 'total', 'label' => 'Total Collected (₹)', 'num' => true],
        ];
    }

    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return $this->cache->remember(
            'fee_collection:'.md5(json_encode($filters).':p'.$perPage.':page'.request()->input('page', 1)),
            $this->tags(),
            300,
            function () use ($filters, $perPage) {
                $p = $this->build($filters)->paginate($perPage)->withQueryString();
                $p->getCollection()->transform(fn ($r) => $this->decorate($r));

                return $p;
            },
        );
    }

    public function all(array $filters): iterable
    {
        return $this->build($filters)->get()->map(fn ($r) => $this->decorate($r));
    }

    protected function decorate($row)
    {
        $row->programme = $row->program_code.' — '.$row->program_name;

        return $row;
    }

    protected function build(array $filters)
    {
        $query = \Modules\Payments\Models\PaymentOrder::query()
            ->selectRaw('DATE(payment_orders.paid_at) as date')
            ->selectRaw('payment_gateways.code as gateway')
            ->selectRaw('payment_orders.purpose as purpose')
            ->selectRaw('programs.code as program_code')
            ->selectRaw('programs.name as program_name')
            ->selectRaw('COUNT(*) as transactions')
            ->selectRaw('SUM(payment_orders.amount) as amount')
            ->selectRaw('SUM(payment_orders.convenience_fee) as convenience')
            ->selectRaw('SUM(payment_orders.gst) as gst')
            ->selectRaw('SUM(payment_orders.total) as total')
            ->join('payment_gateways', 'payment_gateways.id', '=', 'payment_orders.payment_gateway_id')
            ->join('applications', 'applications.id', '=', 'payment_orders.application_id')
            ->join('programs', 'programs.id', '=', 'applications.program_id')
            ->where('payment_orders.status', \Modules\Payments\Models\PaymentOrder::STATUS_PAID)
            ->groupBy('date', 'payment_gateways.code', 'payment_orders.purpose', 'programs.id', 'programs.code', 'programs.name')
            ->orderByDesc('date');

        if (! empty($filters['from'])) {
            $query->whereDate('payment_orders.paid_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('payment_orders.paid_at', '<=', $filters['to']);
        }
        if (! empty($filters['gateway'])) {
            $query->where('payment_gateways.code', $filters['gateway']);
        }
        if (! empty($filters['purpose'])) {
            $query->where('payment_orders.purpose', $filters['purpose']);
        }

        return $query;
    }

    public function summary(array $filters): array
    {
        $rows = $this->all($filters);

        return [
            'total_txns' => collect($rows)->sum('transactions'),
            'total_amount' => round((float) collect($rows)->sum('amount'), 2),
            'total_total' => round((float) collect($rows)->sum('total'), 2),
        ];
    }
}
