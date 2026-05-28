<?php

namespace Modules\Tests\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdmissionTestSchedule extends Model
{
    protected $fillable = [
        'admission_test_config_id',
        'test_date',
        'reporting_time',
        'start_time',
        'end_time',
        'venue',
        'venue_address',
        'admit_cards_published',
        'admit_cards_published_at',
        'admit_cards_published_by',
    ];

    protected function casts(): array
    {
        return [
            'test_date' => 'date',
            'admit_cards_published' => 'boolean',
            'admit_cards_published_at' => 'datetime',
        ];
    }

    public function config(): BelongsTo
    {
        return $this->belongsTo(AdmissionTestConfig::class, 'admission_test_config_id');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(AdmissionTestCandidate::class, 'admission_test_schedule_id');
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admit_cards_published_by');
    }
}
