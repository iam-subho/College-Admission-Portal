<?php

namespace Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academics\Models\Program;
use Modules\Admissions\Database\Factories\EligibilityRuleFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class EligibilityRule extends Model
{
    use HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['rule_type', 'params', 'label', 'is_active'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('eligibility_rule');
    }

    public const TYPE_MIN_PERCENTAGE = 'min_percentage';

    public const TYPE_BOARD_IN = 'board_in';

    public const TYPE_SUBJECT_MINIMUM = 'subject_minimum';

    public const TYPE_AGE_BAND = 'age_band';

    public const TYPE_GAP_YEAR_MAX = 'gap_year_max';

    public const TYPES = [
        self::TYPE_MIN_PERCENTAGE,
        self::TYPE_BOARD_IN,
        self::TYPE_SUBJECT_MINIMUM,
        self::TYPE_AGE_BAND,
        self::TYPE_GAP_YEAR_MAX,
    ];

    protected $fillable = [
        'program_id',
        'rule_type',
        'params',
        'label',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'params' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    protected static function newFactory(): EligibilityRuleFactory
    {
        return EligibilityRuleFactory::new();
    }
}
