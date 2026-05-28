<?php

namespace Modules\Reports\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Reports\Services\ReportCache;

/**
 * Itemised list of all refunds with their slab + status + UTR. The accounts
 * team uses this as the audit document for bank reconciliation.
 */
class WithdrawalRefundRegister extends BaseReport
{
    public function key(): string
    {
        return 'withdrawal_refund_register';
    }

    public function title(): string
    {
        return 'Withdrawal & Refund Register';
    }

    public function group(): string
    {
        return 'financial';
    }

    public function tags(): array
    {
        return [ReportCache::TAG_REFUNDS, ReportCache::TAG_WITHDRAWALS];
    }

    public function filterSchema(): array
    {
        return [
            ['key' => 'from', 'label' => 'From Date', 'type' => 'date'],
            ['key' => 'to', 'label' => 'To Date', 'type' => 'date'],
            ['key' => 'status', 'label' => 'Status', 'type' => 'text'],
        ];
    }

    public function columns(): array
    {
        return [
            ['key' => 'created_at', 'label' => 'Created'],
            ['key' => 'application_number', 'label' => 'Application'],
            ['key' => 'applicant_name', 'label' => 'Applicant'],
            ['key' => 'programme', 'label' => 'Programme'],
            ['key' => 'amount', 'label' => 'Refund (₹)', 'num' => true],
            ['key' => 'deduction_amount', 'label' => 'Deduction (₹)', 'num' => true],
            ['key' => 'policy_slab', 'label' => 'Slab'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'offline_reference', 'label' => 'UTR / Ref'],
            ['key' => 'completed_at', 'label' => 'Completed'],
        ];
    }

    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return $this->cache->remember(
            'refund_register:'.md5(json_encode($filters).':p'.$perPage.':page'.request()->input('page', 1)),
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
        $row->programme = trim(($row->program_code ?? '').' — '.($row->program_name ?? ''), ' —');

        return $row;
    }

    protected function build(array $filters)
    {
        $query = \Modules\Payments\Models\Refund::query()
            ->selectRaw('refunds.created_at, refunds.amount, refunds.deduction_amount, refunds.policy_slab, refunds.status, refunds.offline_reference, refunds.completed_at')
            ->selectRaw('applications.application_number')
            ->selectRaw('users.name as applicant_name')
            ->selectRaw('programs.code as program_code')
            ->selectRaw('programs.name as program_name')
            ->leftJoin('applications', 'applications.id', '=', 'refunds.application_id')
            ->leftJoin('students', 'students.id', '=', 'applications.student_id')
            ->leftJoin('users', 'users.id', '=', 'students.user_id')
            ->leftJoin('programs', 'programs.id', '=', 'applications.program_id')
            ->orderByDesc('refunds.id');

        if (! empty($filters['from'])) {
            $query->whereDate('refunds.created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('refunds.created_at', '<=', $filters['to']);
        }
        if (! empty($filters['status'])) {
            $query->where('refunds.status', $filters['status']);
        }

        return $query;
    }
}
