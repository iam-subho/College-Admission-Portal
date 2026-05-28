<?php

namespace Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Academics\Models\Program;

class AdmissionRound extends Model
{
    public const STATUS_PLANNING = 'planning';

    public const STATUS_OPEN = 'open';

    public const STATUS_MERIT_DRAFTED = 'merit_drafted';

    public const STATUS_MERIT_PUBLISHED = 'merit_published';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_LOCKED = 'locked';

    protected $fillable = [
        'academic_session_id',
        'program_id',
        'round_number',
        'name',
        'status',
        'merit_publish_at',
        'acceptance_window_days',
        'allotment_generated_at',
        'allotment_locked_at',
    ];

    protected function casts(): array
    {
        return [
            'merit_publish_at' => 'datetime',
            'allotment_generated_at' => 'datetime',
            'allotment_locked_at' => 'datetime',
            'acceptance_window_days' => 'integer',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function meritList(): HasOne
    {
        return $this->hasOne(\Modules\Merit\Models\MeritList::class);
    }

    public function seatAllocations(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(\Modules\Seats\Models\SeatAllocation::class);
    }
}
