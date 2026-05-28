<?php

namespace Modules\Merit\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admissions\Models\AdmissionRound;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class MeritList extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total_candidates', 'published_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('merit_list');
    }

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'admission_round_id',
        'merit_formula_id',
        'status',
        'formula_snapshot',
        'total_candidates',
        'max_score',
        'generated_at',
        'generated_by',
        'published_at',
        'published_by',
    ];

    protected function casts(): array
    {
        return [
            'formula_snapshot' => 'array',
            'max_score' => 'decimal:4',
            'generated_at' => 'datetime',
            'published_at' => 'datetime',
        ];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(AdmissionRound::class, 'admission_round_id');
    }

    public function formula(): BelongsTo
    {
        return $this->belongsTo(MeritFormula::class, 'merit_formula_id');
    }

    public function entries(): HasMany
    {
        return $this->hasMany(MeritListEntry::class);
    }

    public function cutoffs(): HasMany
    {
        return $this->hasMany(MeritCutoff::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPublished(): bool
    {
        return $this->status === self::STATUS_PUBLISHED;
    }
}
