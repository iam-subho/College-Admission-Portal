<?php

namespace Modules\Notifications\Events;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Merit\Models\MeritListEntry;

/**
 * Fired PER candidate (one row per merit list entry) so each student gets
 * their own rank/cutoff message. The Merit module already has its own
 * MeritListPublished event for the list as a whole; this wraps each entry.
 */
class MeritPublishedNotificationEvent implements NotifiableEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(public MeritListEntry $entry) {}

    public function eventKey(): string
    {
        return 'merit_list_published';
    }

    public function recipient(): ?User
    {
        return $this->entry->application?->student?->user;
    }

    public function variables(): array
    {
        $e = $this->entry;

        return [
            'name' => $e->application?->student?->user?->name ?? 'Applicant',
            'application_number' => $e->application?->application_number,
            'overall_rank' => $e->overall_rank,
            'category_rank' => $e->category_rank ?? '—',
            'score' => number_format((float) $e->total_score, 2),
            'programme' => $e->meritList?->round?->program?->code,
        ];
    }
}
