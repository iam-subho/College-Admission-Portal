<?php

namespace Modules\Reports\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Reports\ReportRegistry;
use Modules\Reports\Services\AisheExport;
use Modules\Reports\Services\CsvExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportsController extends Controller
{
    public function __construct(protected ReportRegistry $registry) {}

    public function index(?string $key = null): Response
    {
        $reports = $this->registry->grouped();
        $first = $key ?? array_key_first(array_values($reports)[0] ?? []);
        $selected = $key ? $this->registry->find($key) : null;

        return Inertia::render('Admin/Reports', [
            'groups' => $reports,
            'selected' => $selected ? [
                'key' => $selected->key(),
                'title' => $selected->title(),
                'columns' => $selected->columns(),
                'filterSchema' => $selected->filterSchema(),
            ] : null,
        ]);
    }

    public function show(Request $request, string $key): Response
    {
        abort_unless($report = $this->registry->find($key), 404);

        $filters = $report->filtersFromRequest($request);

        return Inertia::render('Admin/Reports', [
            'groups' => $this->registry->grouped(),
            'selected' => [
                'key' => $report->key(),
                'title' => $report->title(),
                'columns' => $report->columns(),
                'filterSchema' => $report->filterSchema(),
            ],
            'filters' => $filters,
            'rows' => $report->paginate($filters)->withQueryString(),
            'summary' => $report->summary($filters),
        ]);
    }

    public function export(Request $request, string $key, CsvExporter $exporter): StreamedResponse
    {
        abort_unless($report = $this->registry->find($key), 404);
        $filters = $report->filtersFromRequest($request);

        return $exporter->stream($report, $filters);
    }

    public function aishe(Request $request, AisheExport $aishe): StreamedResponse
    {
        $session = $request->input('session');

        return $aishe->stream($session ?: null);
    }
}
