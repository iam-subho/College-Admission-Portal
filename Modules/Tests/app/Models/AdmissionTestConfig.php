<?php

namespace Modules\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Academics\Models\Program;
use Modules\Admissions\Models\AcademicSession;

class AdmissionTestConfig extends Model
{
    protected $fillable = [
        'program_id',
        'academic_session_id',
        'is_test_enabled',
        'max_marks',
        'qualifying_marks',
        'test_weight',
        'marks_weight',
        'negative_marking_rule',
        'syllabus_url',
        'instructions',
    ];

    protected function casts(): array
    {
        return [
            'is_test_enabled' => 'boolean',
            'max_marks' => 'decimal:2',
            'qualifying_marks' => 'decimal:2',
            'test_weight' => 'decimal:2',
            'marks_weight' => 'decimal:2',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }

    public function schedule(): HasOne
    {
        return $this->hasOne(AdmissionTestSchedule::class);
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(AdmissionTestCandidate::class, 'admission_test_schedule_id', 'id')
            ->whereHas('schedule', fn ($q) => $q->where('admission_test_config_id', $this->id));
    }
}
