<?php

namespace Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Notice extends Model
{
    use LogsActivity;

    public const TAB_LATEST = 'latest';

    public const TAB_ADMISSIONS = 'admissions';

    public const TAB_EXAMINATION = 'examination';

    protected $fillable = [
        'notice_date',
        'title',
        'tab',
        'url',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'notice_date' => 'date',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['notice_date', 'title', 'tab', 'url', 'is_active', 'sort_order'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('notice');
    }

    public static function tabs(): array
    {
        return [self::TAB_LATEST, self::TAB_ADMISSIONS, self::TAB_EXAMINATION];
    }
}
