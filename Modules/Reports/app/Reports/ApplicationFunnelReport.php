<?php

namespace Modules\Reports\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Modules\Admissions\Models\Application;
use Modules\Reports\Services\ReportCache;
use Modules\Seats\Models\SeatAllocation;

/**
 * One row per programme: count of applications at each funnel stage.
 * Drafts → Submitted → Paid → Verdict (pass / override_pass) → Allotted →
 * Admitted → Withdrawn. Useful for conversion analysis.
 */
class ApplicationFunnelReport extends BaseReport
{
    public function key(): string
    {
        return 'application_funnel';
    }

    public function title(): string
    {
        return 'Application Funnel';
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
            ['key' => 'session', 'label' => 'Session Code', 'type' => 'text'],
        ];
    }

    public function columns(): array
    {
        return [
            ['key' => 'programme', 'label' => 'Programme'],
            ['key' => 'intake', 'label' => 'Intake', 'num' => true],
            ['key' => 'draft', 'label' => 'Draft', 'num' => true],
            ['key' => 'submitted', 'label' => 'Submitted', 'num' => true],
            ['key' => 'paid', 'label' => 'Paid', 'num' => true],
            ['key' => 'eligible', 'label' => 'Pass Eligibility', 'num' => true],
            ['key' => 'allotted', 'label' => 'Allotted', 'num' => true],
            ['key' => 'admitted', 'label' => 'Admitted', 'num' => true],
            ['key' => 'withdrawn', 'label' => 'Withdrawn', 'num' => true],
            ['key' => 'conversion_pct', 'label' => 'Admit %', 'num' => true],
        ];
    }

    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        $rows = $this->cache->remember(
            'application_funnel:'.md5(json_encode($filters)),
            $this->tags(),
            300,
            fn () => $this->build($filters),
        );

        $page = (int) request()->input('page', 1);
        $sliced = array_slice($rows, ($page - 1) * $perPage, $perPage);

        return new Paginator(
            $sliced,
            count($rows),
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()],
        );
    }

    public function all(array $filters): iterable
    {
        return $this->build($filters);
    }

    protected function build(array $filters): array
    {
        $apps = Application::query()
            ->selectRaw('program_id, status, payment_status, eligibility_verdict, COUNT(*) as cnt')
            ->groupBy('program_id', 'status', 'payment_status', 'eligibility_verdict');

        if (! empty($filters['session'])) {
            $apps->whereHas('session', fn ($q) => $q->where('code', $filters['session']));
        }
        $apps = $apps->get()->groupBy('program_id');

        // groupBy on an Eloquent Collection returns another Eloquent Collection
        // whose ->only() expects model keys (calls getKey() on items). Convert to
        // a base Collection so ->only(keys) keys-by-key as we need.
        $seats = SeatAllocation::query()
            ->selectRaw('application_id, status')
            ->get()
            ->groupBy('application_id')
            ->collect();

        $programs = \Modules\Academics\Models\Program::with('department:id,code,name')
            ->orderBy('name')->get(['id', 'code', 'name', 'intake_capacity']);

        $rows = [];
        foreach ($programs as $p) {
            $progApps = $apps->get($p->id) ?? collect();
            $draft = (int) $progApps->where('status', Application::STATUS_DRAFT)->sum('cnt');
            $submitted = (int) $progApps->whereIn('status', [
                Application::STATUS_SUBMITTED, Application::STATUS_VERIFIED, Application::STATUS_REJECTED,
            ])->sum('cnt');
            $paid = (int) $progApps->where(fn ($a) => in_array($a->payment_status, [
                Application::PAYMENT_PAID, Application::PAYMENT_COVERED, Application::PAYMENT_NOT_REQUIRED,
            ], true) && in_array($a->status, [
                Application::STATUS_SUBMITTED, Application::STATUS_VERIFIED, Application::STATUS_REJECTED,
            ], true))->sum('cnt');
            $eligible = (int) $progApps->whereIn('eligibility_verdict', [
                Application::VERDICT_PASS, Application::VERDICT_OVERRIDE_PASS,
            ])->where(fn ($a) => in_array($a->status, [
                Application::STATUS_SUBMITTED, Application::STATUS_VERIFIED,
            ], true))->sum('cnt');

            // Look at the seat allocations attached to this programme's applications.
            $appIds = $progApps->pluck('program_id')->count()
                ? Application::where('program_id', $p->id)->pluck('id')->all()
                : [];
            $progSeats = $seats->only($appIds)->flatten(1);

            $allotted = $progSeats->whereIn('status', [
                SeatAllocation::STATUS_ALLOTTED,
                SeatAllocation::STATUS_ACCEPTED,
                SeatAllocation::STATUS_ADMITTED,
            ])->count();
            $admitted = $progSeats->where('status', SeatAllocation::STATUS_ADMITTED)->count();
            $withdrawn = (int) $progApps->where('status', Application::STATUS_WITHDRAWN)->sum('cnt');

            $rows[] = [
                'programme' => $p->code.' — '.$p->name,
                'intake' => $p->intake_capacity,
                'draft' => $draft,
                'submitted' => $submitted,
                'paid' => $paid,
                'eligible' => $eligible,
                'allotted' => $allotted,
                'admitted' => $admitted,
                'withdrawn' => $withdrawn,
                'conversion_pct' => $submitted > 0 ? round(($admitted / $submitted) * 100, 2) : 0,
            ];
        }

        return $rows;
    }
}
