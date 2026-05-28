<?php

namespace Modules\Tests\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Admissions\Models\Application;

class AdmissionTestCandidate extends Model
{
    protected $fillable = [
        'application_id',
        'admission_test_schedule_id',
        'roll_number',
        'roll_assigned_at',
        'admit_card_published',
        'admit_card_published_at',
        'admit_card_downloaded_at',
    ];

    protected function casts(): array
    {
        return [
            'roll_assigned_at' => 'datetime',
            'admit_card_published' => 'boolean',
            'admit_card_published_at' => 'datetime',
            'admit_card_downloaded_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(AdmissionTestSchedule::class, 'admission_test_schedule_id');
    }

    public function score(): HasOne
    {
        return $this->hasOne(AdmissionTestScore::class);
    }
}
