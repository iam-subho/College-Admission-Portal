<?php

namespace Modules\Tests\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdmissionTestScore extends Model
{
    public const ATTENDANCE_PRESENT = 'present';

    public const ATTENDANCE_ABSENT = 'absent';

    public const ENTERED_VIA_MANUAL = 'manual';

    public const ENTERED_VIA_CSV = 'csv';

    protected $fillable = [
        'admission_test_candidate_id',
        'raw_marks',
        'attendance',
        'entered_via',
        'entered_by',
        'entered_at',
        'is_locked',
        'locked_at',
    ];

    protected function casts(): array
    {
        return [
            'raw_marks' => 'decimal:2',
            'is_locked' => 'boolean',
            'entered_at' => 'datetime',
            'locked_at' => 'datetime',
        ];
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(AdmissionTestCandidate::class, 'admission_test_candidate_id');
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by');
    }
}
