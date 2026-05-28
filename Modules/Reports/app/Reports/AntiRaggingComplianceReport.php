<?php

namespace Modules\Reports\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Reports\Services\ReportCache;

/**
 * UGC anti-ragging undertaking compliance: count of applicants who accepted
 * the declaration vs. those who didn't, per (session, programme). Used in
 * the annual anti-ragging compliance affidavit to UGC.
 */
class AntiRaggingComplianceReport extends BaseReport
{
    public function key(): string
    {
        return 'anti_ragging_compliance';
    }

    public function title(): string
    {
        return 'Anti-Ragging Compliance';
    }

    public function group(): string
    {
        return 'compliance';
    }

    public function tags(): array
    {
        return [ReportCache::TAG_APPLICATIONS];
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
            ['key' => 'session', 'label' => 'Session'],
            ['key' => 'programme', 'label' => 'Programme'],
            ['key' => 'submitted_total', 'label' => 'Submitted', 'num' => true],
            ['key' => 'accepted', 'label' => 'Accepted Anti-Ragging', 'num' => true],
            ['key' => 'not_accepted', 'label' => 'Not Accepted', 'num' => true],
            ['key' => 'compliance_pct', 'label' => 'Compliance %', 'num' => true],
        ];
    }

    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return $this->cache->remember(
            'anti_rag:'.md5(json_encode($filters).':p'.$perPage.':page'.request()->input('page', 1)),
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
        $query = \Modules\Admissions\Models\Application::query()
            ->selectRaw('academic_sessions.code as session')
            ->selectRaw('programs.code as program_code')
            ->selectRaw('programs.name as program_name')
            ->selectRaw('COUNT(*) as submitted_total')
            ->selectRaw('SUM(CASE WHEN applications.declaration_anti_ragging = 1 THEN 1 ELSE 0 END) as accepted')
            ->selectRaw('SUM(CASE WHEN applications.declaration_anti_ragging = 0 OR applications.declaration_anti_ragging IS NULL THEN 1 ELSE 0 END) as not_accepted')
            ->selectRaw('ROUND(SUM(CASE WHEN applications.declaration_anti_ragging = 1 THEN 1 ELSE 0 END) * 100.0 / NULLIF(COUNT(*), 0), 2) as compliance_pct')
            ->join('programs', 'programs.id', '=', 'applications.program_id')
            ->join('academic_sessions', 'academic_sessions.id', '=', 'applications.academic_session_id')
            ->whereIn('applications.status', ['submitted', 'verified', 'rejected', 'withdrawn'])
            ->groupBy('academic_sessions.code', 'programs.id', 'programs.code', 'programs.name')
            ->orderBy('academic_sessions.code')
            ->orderBy('programs.code');

        if (! empty($filters['session'])) {
            $query->where('academic_sessions.code', $filters['session']);
        }

        return $query;
    }
}
