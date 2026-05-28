<?php

namespace Modules\Merit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admissions\Models\ReservationCategory;

class MeritCutoff extends Model
{
    protected $fillable = [
        'merit_list_id',
        'reservation_category_id',
        'seats_available',
        'cutoff_score',
        'last_rank',
        'candidates_in_category',
    ];

    protected function casts(): array
    {
        return [
            'cutoff_score' => 'decimal:4',
        ];
    }

    public function meritList(): BelongsTo
    {
        return $this->belongsTo(MeritList::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ReservationCategory::class, 'reservation_category_id');
    }
}
