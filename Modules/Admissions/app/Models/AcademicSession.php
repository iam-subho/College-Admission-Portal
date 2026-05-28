<?php

namespace Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Admissions\Database\Factories\AcademicSessionFactory;

class AcademicSession extends Model
{
    use HasFactory, SoftDeletes;

    public const STATUS_PLANNING = 'planning';

    public const STATUS_OPEN = 'open';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    public const PAYMENT_MODE_PER_PROGRAMME = 'per_programme';

    public const PAYMENT_MODE_ONE_TIME = 'one_time';

    protected $fillable = [
        'code',
        'name',
        'commencement_date',
        'application_open_date',
        'application_close_date',
        'is_active',
        'status',
        'payment_mode',
        'application_fee',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'commencement_date' => 'date',
            'application_open_date' => 'date',
            'application_close_date' => 'date',
            'application_fee' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function reservationMatrix(): HasMany
    {
        return $this->hasMany(ProgramReservation::class);
    }

    protected static function newFactory(): AcademicSessionFactory
    {
        return AcademicSessionFactory::new();
    }
}
