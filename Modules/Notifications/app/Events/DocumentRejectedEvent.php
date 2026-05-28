<?php

namespace Modules\Notifications\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Documents\Models\UploadedDocument;

class DocumentRejectedEvent implements NotifiableEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public UploadedDocument $document, public string $reason) {}

    public function eventKey(): string
    {
        return 'document_rejected';
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
            'reason' => $this->reason,
        ];
    }
}
