<?php

namespace Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academics\Models\Program;

class ProgramReservation extends Model
{
    protected $table = 'program_reservation_matrix';

    protected $fillable = [
        'academic_session_id',
        'program_id',
        'reservation_category_id',
        'seats',
        'relaxation_percent',
    ];

    protected function casts(): array
    {
        return [
            'seats' => 'integer',
            'relaxation_percent' => 'decimal:2',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(ReservationCategory::class, 'reservation_category_id');
    }
}
