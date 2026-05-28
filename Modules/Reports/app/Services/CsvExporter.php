<?php

namespace Modules\Reports\Services;

use Illuminate\Http\Response;
use League\Csv\Writer;
use Modules\Reports\Reports\BaseReport;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Streams a report's rows to a downloadable CSV. Uses league/csv (already
 * installed). The CSV writer streams to php://output so memory stays low
 * regardless of row count.
 */
class CsvExporter
{
    public function stream(BaseReport $report, array $filters): StreamedResponse
    {
        $filename = $report->exportFilename($filters).'.csv';

        return new StreamedResponse(function () use ($report, $filters) {
            $csv = Writer::createFromStream(fopen('php://output', 'w'));

            // Header row — column labels.
            $csv->insertOne(array_map(fn ($c) => (string) ($c['label'] ?? $c['key']), $report->columns()));

            // Data rows.
            foreach ($report->all($filters) as $row) {
                $line = [];
                foreach ($report->columns() as $col) {
                    $value = is_array($row) ? ($row[$col['key']] ?? null) : data_get($row, $col['key']);
                    if (is_array($value) || is_object($value)) {
                        $value = json_encode($value);
                    }
                    $line[] = $value ?? '';
                }
                $csv->insertOne($line);
            }
        }, Response::HTTP_OK, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="'.addslashes($filename).'"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }
}
