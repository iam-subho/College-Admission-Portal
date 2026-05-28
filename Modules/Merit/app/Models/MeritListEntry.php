<?php

namespace Modules\Merit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\ReservationCategory;

class MeritListEntry extends Model
{
    protected $fillable = [
        'merit_list_id',
        'application_id',
        'reservation_category_id',
        'overall_rank',
        'category_rank',
        'total_score',
        'test_score',
        'test_pct',
        'marks_pct',
        'tie_break_data',
        'is_qualifying',
        'is_absent',
    ];

    protected function casts(): array
    {
        return [
            'total_score' => 'decimal:4',
            'test_score' => 'decimal:2',
            'test_pct' => 'decimal:2',
            'marks_pct' => 'decimal:2',
            'tie_break_data' => 'array',
            'is_qualifying' => 'boolean',
            'is_absent' => 'boolean',
        ];
    }

    public function meritList(): BelongsTo
    {
        return $this->belongsTo(MeritList::class);
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ReservationCategory::class, 'reservation_category_id');
    }
}
