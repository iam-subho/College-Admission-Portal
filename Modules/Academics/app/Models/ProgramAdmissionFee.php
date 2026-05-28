<?php

namespace Modules\Academics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admissions\Models\ReservationCategory;

class ProgramAdmissionFee extends Model
{
    protected $fillable = [
        'program_id',
        'reservation_category_id',
        'admission_fee',
    ];

    protected function casts(): array
    {
        return [
            'admission_fee' => 'decimal:2',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ReservationCategory::class, 'reservation_category_id');
    }
}
