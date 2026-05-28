<?php

namespace Modules\Reports\Reports;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Reports\Services\ReportCache;

/**
 * Rejected documents grouped by reason + document type. Lets admin spot
 * patterns ("students keep uploading low-res Aadhaar scans") and update
 * upload instructions accordingly.
 */
class DocumentRejectionReport extends BaseReport
{
    public function key(): string
    {
        return 'document_rejections';
    }

    public function title(): string
    {
        return 'Document Rejection Reasons';
    }

    public function group(): string
    {
        return 'operational';
    }

    public function tags(): array
    {
        return [ReportCache::TAG_DOCUMENTS];
    }

    public function filterSchema(): array
    {
        return [
            ['key' => 'from', 'label' => 'From Date', 'type' => 'date'],
            ['key' => 'to', 'label' => 'To Date', 'type' => 'date'],
        ];
    }

    public function columns(): array
    {
        return [
            ['key' => 'document_type', 'label' => 'Document Type'],
            ['key' => 'reason', 'label' => 'Reason'],
            ['key' => 'count', 'label' => 'Count', 'num' => true],
            ['key' => 'last_rejected_at', 'label' => 'Last Rejected'],
        ];
    }

    public function paginate(array $filters, int $perPage = 50): LengthAwarePaginator
    {
        return $this->cache->remember(
            'doc_rejections:'.md5(json_encode($filters).':p'.$perPage.':page'.request()->input('page', 1)),
            $this->tags(),
            300,
            fn () => $this->build($filters)->paginate($perPage)->withQueryString(),
        );
    }

    protected function build(array $filters)
    {
        $query = \Modules\Documents\Models\DocumentVerification::query()
            ->selectRaw('document_types.label as document_type')
            ->selectRaw('document_verifications.remark as reason')
            ->selectRaw('COUNT(*) as count')
            ->selectRaw('MAX(document_verifications.created_at) as last_rejected_at')
            ->where('document_verifications.action', 'reject')
            ->join('uploaded_documents', 'uploaded_documents.id', '=', 'document_verifications.uploaded_document_id')
            ->join('document_types', 'document_types.id', '=', 'uploaded_documents.document_type_id')
            ->groupBy('document_types.label', 'document_verifications.remark')
            ->orderByDesc('count');

        if (! empty($filters['from'])) {
            $query->whereDate('document_verifications.created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('document_verifications.created_at', '<=', $filters['to']);
        }

        return $query;
    }
}
