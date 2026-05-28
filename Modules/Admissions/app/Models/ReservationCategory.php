<?php

namespace Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admissions\Database\Factories\ReservationCategoryFactory;

class ReservationCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'is_horizontal',
        'default_percentage',
        'is_active',
        'ordering',
    ];

    protected function casts(): array
    {
        return [
            'is_horizontal' => 'boolean',
            'is_active' => 'boolean',
            'default_percentage' => 'decimal:2',
        ];
    }

    public function matrixEntries(): HasMany
    {
        return $this->hasMany(ProgramReservation::class);
    }

    protected static function newFactory(): ReservationCategoryFactory
    {
        return ReservationCategoryFactory::new();
    }
}
