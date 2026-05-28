<?php

namespace Modules\Reports\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Admissions\Models\Application;
use Modules\Reports\Services\ReportCache;

/**
 * One row per (date, programme). Counts of applications by stage. Useful
 * for the admissions office to see daily activity.
 */
class DailyAdmissionReport extends BaseReport
{
    public function key(): string
    {
        return 'daily_admission';
    }

    public function title(): string
    {
        return 'Daily Admission Activity';
    }

    public function group(): string
    {
        return 'operational';
    }

    public function tags(): array
    {
        return [ReportCache::TAG_APPLICATIONS, ReportCache::TAG_SEATS];
    }

    public function filterSchema(): array
    {
        return [
            ['key' => 'from', 'label' => 'From Date', 'type' => 'date'],
            ['key' => 'to', 'label' => 'To Date', 'type' => 'date'],
            ['key' => 'session', 'label' => 'Session Code', 'type' => 'text'],
        ];
    }

    public function columns(): array
    {
        return [
            ['key' => 'date', 'label' => 'Date'],
            ['key' => 'session', 'label' => 'Session'],
            ['key' => 'programme', 'label' => 'Programme'],
            ['key' => 'submitted', 'label' => 'Submitted', 'num' => true],
            ['key' => 'paid', 'label' => 'Paid', 'num' => true],
            ['key' => 'allotted', 'label' => 'Allotted', 'num' => true],
            ['key' => 'admitted', 'label' => 'Admitted', 'num' => true],
            ['key' => 'withdrawn', 'label' => 'Withdrawn', 'num' => true],
        ];
    }

    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return $this->cache->remember(
            'daily_admission:'.md5(json_encode($filters).':p'.$perPage.':page'.request()->input('page', 1)),
            $this->tags(),
            300,
            function () use ($filters, $perPage) {
                $paginator = $this->build($filters)->paginate($perPage)->withQueryString();
                $paginator->getCollection()->transform(fn ($r) => $this->decorate($r));

                return $paginator;
            },
        );
    }

    public function all(array $filters): iterable
    {
        return $this->build($filters)->get()->map(fn ($r) => $this->decorate($r));
    }

    /** Compose the human-readable programme label after the query (portable across DBs). */
    protected function decorate($row)
    {
        $row->programme = $row->program_code.' — '.$row->program_name;

        return $row;
    }

    protected function build(array $filters)
    {
        $query = Application::query()
            ->selectRaw('DATE(applications.submitted_at) as date')
            ->selectRaw('academic_sessions.code as session')
            ->selectRaw('programs.code as program_code')
            ->selectRaw('programs.name as program_name')
            ->selectRaw('COUNT(*) as submitted')
            ->selectRaw('SUM(CASE WHEN payment_status IN (?, ?, ?) THEN 1 ELSE 0 END) as paid',
                [Application::PAYMENT_PAID, Application::PAYMENT_COVERED, Application::PAYMENT_NOT_REQUIRED])
            ->selectRaw('SUM(CASE WHEN EXISTS (SELECT 1 FROM seat_allocations WHERE seat_allocations.application_id = applications.id AND seat_allocations.status IN (?, ?, ?)) THEN 1 ELSE 0 END) as allotted',
                ['allotted', 'accepted', 'admitted'])
            ->selectRaw('SUM(CASE WHEN EXISTS (SELECT 1 FROM seat_allocations WHERE seat_allocations.application_id = applications.id AND seat_allocations.status = ?) THEN 1 ELSE 0 END) as admitted',
                ['admitted'])
            ->selectRaw('SUM(CASE WHEN applications.status = ? THEN 1 ELSE 0 END) as withdrawn',
                [Application::STATUS_WITHDRAWN])
            ->join('programs', 'programs.id', '=', 'applications.program_id')
            ->join('academic_sessions', 'academic_sessions.id', '=', 'applications.academic_session_id')
            ->whereNotNull('applications.submitted_at')
            ->groupBy('date', 'academic_sessions.code', 'programs.id', 'programs.code', 'programs.name')
            ->orderByDesc('date');

        if (! empty($filters['from'])) {
            $query->whereDate('applications.submitted_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('applications.submitted_at', '<=', $filters['to']);
        }
        if (! empty($filters['session'])) {
            $query->where('academic_sessions.code', $filters['session']);
        }

        return $query;
    }

    public function summary(array $filters): array
    {
        $rows = $this->all($filters);

        return [
            'total_days' => is_countable($rows) ? count($rows) : 0,
            'total_submitted' => collect($rows)->sum('submitted'),
            'total_paid' => collect($rows)->sum('paid'),
            'total_admitted' => collect($rows)->sum('admitted'),
        ];
    }
}
