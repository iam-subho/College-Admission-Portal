<?php

namespace Modules\Notifications\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Documents\Models\UploadedDocument;

class DocumentApprovedEvent implements NotifiableEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public UploadedDocument $document) {}

    public function eventKey(): string
    {
        return 'document_approved';
    }

    public function recipient(): ?User
    {
        return $this->document->student?->user;
    }

    public function variables(): array
    {
        $d = $this->document;

        return [
            'name' => $d->student?->user?->name ?? 'Applicant',
            'document' => $d->type?->label ?? 'Document',
            'original_name' => $d->original_name,
            'approved_at' => now()->format('d M Y, h:i A'),
        ];
    }
}
