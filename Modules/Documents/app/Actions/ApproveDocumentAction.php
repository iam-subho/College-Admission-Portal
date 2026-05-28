<?php

namespace Modules\Documents\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Documents\Models\DocumentVerification;
use Modules\Documents\Models\UploadedDocument;

class ApproveDocumentAction
{
    public function execute(UploadedDocument $doc, User $by, ?string $remark = null): UploadedDocument
    {
        return DB::transaction(function () use ($doc, $by, $remark) {
            $doc->forceFill([
                'status' => UploadedDocument::STATUS_APPROVED,
                'rejection_reason' => null,
                'status_changed_at' => now(),
            ])->save();

            DocumentVerification::create([
                'uploaded_document_id' => $doc->id,
                'verified_by' => $by->id,
                'action' => DocumentVerification::ACTION_APPROVED,
                'remark' => $remark,
                'decided_at' => now(),
            ]);

            activity('document')
                ->performedOn($doc)
                ->causedBy($by)
                ->withProperties(['remark' => $remark])
                ->log('Document approved');

            return $doc->fresh();
        });
    }
}
