<?php

namespace Modules\Reports\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Reports\Services\ReportCache;

/**
 * Every report extends this. Subclasses declare a unique key, a title, the
 * cache tags they depend on, the column metadata, and a build($filters)
 * method that returns either a paginator (for paginated tables), a
 * Collection (for full CSV exports), or a Builder (for cursor exports).
 */
abstract class BaseReport
{
    public function __construct(protected ReportCache $cache) {}

    /** Stable key used in routes / sidebar selection (e.g. 'daily_admission'). */
    abstract public function key(): string;

    /** Human-readable title shown in the sidebar / breadcrumb. */
    abstract public function title(): string;

    /** Group/category in the sidebar: 'operational' | 'financial' | 'compliance' | 'statutory'. */
    abstract public function group(): string;

    /**
     * Cache tags this report depends on. ReportCache wipes these tags on
     * relevant model writes (via observers). Subclasses pick from:
     * applications, payments, refunds, merit_lists, seat_allocations,
     * documents, withdrawals.
     *
     * @return string[]
     */
    abstract public function tags(): array;

    /**
     * Filter schema for the report (rendered as a form). Each filter has
     * a key, label, type (text|date|select), and optional options for select.
     *
     * @return array<int, array<string, mixed>>
     */
    public function filterSchema(): array
    {
        return [];
    }

    /**
     * Column metadata for the table + CSV header row.
     * Each entry: { key, label, num?: bool }
     *
     * @return array<int, array<string, mixed>>
     */
    abstract public function columns(): array;

    /**
     * Return the paginated dataset for the in-page table.
     */
    abstract public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator;

    /**
     * Return ALL rows (no pagination) for the CSV export. Default streams
     * via the same query but unpaginated — subclasses can override for
     * a more efficient cursor.
     */
    public function all(array $filters): iterable
    {
        return $this->paginate($filters, perPage: 10000)->items();
    }

    /**
     * Total / summary line shown above the table (optional).
     *
     * @return array<string, scalar|null>
     */
    public function summary(array $filters): array
    {
        return [];
    }

    /**
     * Filename for the CSV export (no extension).
     */
    public function exportFilename(array $filters): string
    {
        return $this->key().'_'.now()->format('Y-m-d');
    }

    /**
     * Resolve filters from a Request — validates against filterSchema().
     */
    public function filtersFromRequest(Request $request): array
    {
        $rules = [];
        foreach ($this->filterSchema() as $f) {
            $rules[$f['key']] = match ($f['type'] ?? 'text') {
                'date' => ['nullable', 'date'],
                'integer' => ['nullable', 'integer'],
                default => ['nullable', 'string', 'max:120'],
            };
        }

        return $request->validate($rules);
    }
}
