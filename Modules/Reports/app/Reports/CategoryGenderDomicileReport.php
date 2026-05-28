<?php

namespace Modules\Reports\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Reports\Services\ReportCache;

/**
 * Pivot: category × gender × domicile-state. Counts admitted students for
 * reservation compliance reporting to UGC / state govt.
 */
class CategoryGenderDomicileReport extends BaseReport
{
    public function key(): string
    {
        return 'category_gender_domicile';
    }

    public function title(): string
    {
        return 'Category × Gender × Domicile';
    }

    public function group(): string
    {
        return 'compliance';
    }

    public function tags(): array
    {
        return [ReportCache::TAG_APPLICATIONS, ReportCache::TAG_SEATS];
    }

    public function filterSchema(): array
    {
        return [
            ['key' => 'session', 'label' => 'Session Code', 'type' => 'text'],
            ['key' => 'stage', 'label' => 'Stage (submitted | admitted)', 'type' => 'text'],
        ];
    }

    public function columns(): array
    {
        return [
            ['key' => 'category', 'label' => 'Category'],
            ['key' => 'gender', 'label' => 'Gender'],
            ['key' => 'domicile_state', 'label' => 'Domicile State'],
            ['key' => 'count', 'label' => 'Count', 'num' => true],
        ];
    }

    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return $this->cache->remember(
            'cat_gen_dom:'.md5(json_encode($filters).':p'.$perPage.':page'.request()->input('page', 1)),
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
        $stage = $filters['stage'] ?? 'admitted';

        $query = \Modules\Admissions\Models\Application::query()
            ->selectRaw('COALESCE(reservation_categories.code, ?) as category', ['UR'])
            ->selectRaw('COALESCE(students.gender, ?) as gender', ['Unspecified'])
            ->selectRaw('COALESCE(students.domicile_state, ?) as domicile_state', ['Unspecified'])
            ->selectRaw('COUNT(*) as count')
            ->join('students', 'students.id', '=', 'applications.student_id')
            ->leftJoin('reservation_categories', 'reservation_categories.id', '=', 'students.reservation_category_id')
            ->join('academic_sessions', 'academic_sessions.id', '=', 'applications.academic_session_id')
            ->groupBy('category', 'gender', 'domicile_state')
            ->orderBy('category')
            ->orderBy('gender')
            ->orderBy('domicile_state');

        if ($stage === 'admitted') {
            $query->whereExists(function ($q) {
                $q->select(\DB::raw(1))
                    ->from('seat_allocations')
                    ->whereColumn('seat_allocations.application_id', 'applications.id')
                    ->where('seat_allocations.status', 'admitted');
            });
        } else {
            $query->whereIn('applications.status', ['submitted', 'verified']);
        }

        if (! empty($filters['session'])) {
            $query->where('academic_sessions.code', $filters['session']);
        }

        return $query;
    }
}
