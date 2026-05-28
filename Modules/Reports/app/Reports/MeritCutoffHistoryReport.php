<?php

namespace Modules\Reports\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Reports\Services\ReportCache;

/**
 * Historical cutoff scores per (round, programme, category). Useful for
 * advising prospective applicants and for compliance audits.
 */
class MeritCutoffHistoryReport extends BaseReport
{
    public function key(): string
    {
        return 'merit_cutoff_history';
    }

    public function title(): string
    {
        return 'Merit Cutoff History';
    }

    public function group(): string
    {
        return 'compliance';
    }

    public function tags(): array
    {
        return [ReportCache::TAG_MERIT];
    }

    public function filterSchema(): array
    {
        return [
            ['key' => 'session', 'label' => 'Session Code', 'type' => 'text'],
            ['key' => 'programme', 'label' => 'Programme Code', 'type' => 'text'],
        ];
    }

    public function columns(): array
    {
        return [
            ['key' => 'session', 'label' => 'Session'],
            ['key' => 'programme', 'label' => 'Programme'],
            ['key' => 'round_number', 'label' => 'Round', 'num' => true],
            ['key' => 'category', 'label' => 'Category'],
            ['key' => 'seats', 'label' => 'Seats', 'num' => true],
            ['key' => 'cutoff_score', 'label' => 'Cutoff', 'num' => true],
            ['key' => 'last_rank', 'label' => 'Last Rank', 'num' => true],
            ['key' => 'candidates', 'label' => 'Candidates', 'num' => true],
            ['key' => 'published_at', 'label' => 'Published'],
        ];
    }

    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return $this->cache->remember(
            'merit_cutoff:'.md5(json_encode($filters).':p'.$perPage.':page'.request()->input('page', 1)),
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
        $query = \Modules\Merit\Models\MeritCutoff::query()
            ->selectRaw('academic_sessions.code as session')
            ->selectRaw('programs.code as program_code')
            ->selectRaw('programs.name as program_name')
            ->selectRaw('admission_rounds.round_number as round_number')
            ->selectRaw('reservation_categories.code as category')
            ->selectRaw('merit_cutoffs.seats_available as seats')
            ->selectRaw('merit_cutoffs.cutoff_score as cutoff_score')
            ->selectRaw('merit_cutoffs.last_rank as last_rank')
            ->selectRaw('merit_cutoffs.candidates_in_category as candidates')
            ->selectRaw('merit_lists.published_at as published_at')
            ->join('merit_lists', 'merit_lists.id', '=', 'merit_cutoffs.merit_list_id')
            ->join('admission_rounds', 'admission_rounds.id', '=', 'merit_lists.admission_round_id')
            ->join('programs', 'programs.id', '=', 'admission_rounds.program_id')
            ->join('academic_sessions', 'academic_sessions.id', '=', 'admission_rounds.academic_session_id')
            ->join('reservation_categories', 'reservation_categories.id', '=', 'merit_cutoffs.reservation_category_id')
            ->where('merit_lists.status', 'published')
            ->orderByDesc('merit_lists.published_at')
            ->orderBy('programs.code')
            ->orderBy('admission_rounds.round_number')
            ->orderBy('reservation_categories.ordering');

        if (! empty($filters['session'])) {
            $query->where('academic_sessions.code', $filters['session']);
        }
        if (! empty($filters['programme'])) {
            $query->where('programs.code', $filters['programme']);
        }

        return $query;
    }
}
