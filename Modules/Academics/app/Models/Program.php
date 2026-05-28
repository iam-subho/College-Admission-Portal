<?php

namespace Modules\Academics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Academics\Database\Factories\ProgramFactory;
use Modules\Admissions\Models\ProgramReservation;

class Program extends Model
{
    use HasFactory, SoftDeletes;

    public const TYPE_UG = 'UG';

    public const TYPE_PG = 'PG';

    protected $fillable = [
        'code',
        'name',
        'department_id',
        'type',
        'duration_years',
        'total_credits',
        'intake_capacity',
        'application_fee',
        'admission_fee',
        'description',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'duration_years' => 'integer',
            'total_credits' => 'integer',
            'intake_capacity' => 'integer',
            'application_fee' => 'decimal:2',
            'admission_fee' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(ProgramReservation::class);
    }

    public function coursePools(): HasMany
    {
        return $this->hasMany(ProgrammeCoursePool::class);
    }

    public function applicationFeeOverrides(): HasMany
    {
        return $this->hasMany(ProgramApplicationFee::class);
    }

    public function admissionFeeOverrides(): HasMany
    {
        return $this->hasMany(ProgramAdmissionFee::class);
    }

    protected static function newFactory(): ProgramFactory
    {
        return ProgramFactory::new();
    }
}
