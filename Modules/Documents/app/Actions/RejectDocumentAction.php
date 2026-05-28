<?php

namespace Modules\Documents\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Documents\Models\DocumentVerification;
use Modules\Documents\Models\UploadedDocument;

class RejectDocumentAction
{
    public function execute(UploadedDocument $doc, User $by, string $reason): UploadedDocument
    {
        return DB::transaction(function () use ($doc, $by, $reason) {
            $doc->forceFill([
                'status' => UploadedDocument::STATUS_REJECTED,
                'rejection_reason' => $reason,
                'status_changed_at' => now(),
            ])->save();

            DocumentVerification::create([
                'uploaded_document_id' => $doc->id,
                'verified_by' => $by->id,
                'action' => DocumentVerification::ACTION_REJECTED,
                'remark' => $reason,
                'decided_at' => now(),
            ]);

            activity('document')
                ->performedOn($doc)
                ->causedBy($by)
                ->withProperties(['reason' => $reason])
                ->log('Document rejected');

            return $doc->fresh();
        });
    }
}
