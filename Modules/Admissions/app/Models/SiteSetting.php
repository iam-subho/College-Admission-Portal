<?php

namespace Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SiteSetting extends Model
{
    use LogsActivity;

    public const CACHE_KEY = 'site_settings:all';

    protected $fillable = [
        'key',
        'value',
        'group',
        'label',
        'input_type',
        'help',
        'sort_order',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['key', 'value', 'group', 'label'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('site_setting');
    }

    /** Return the raw stored key=>value map, cached for an hour. */
    public static function map(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, fn () => self::pluck('value', 'key')->all());
    }

    /** @return array<string, string|null> Stored rows on top of the config fallbacks. */
    public static function resolved(): array
    {
        return array_replace((array) config('admissions.identity', []), self::map());
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $map = self::map();

        if (array_key_exists($key, $map)) {
            return $map[$key];
        }

        return $default ?? config("admissions.identity.{$key}");
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget(self::CACHE_KEY));
        static::deleted(fn () => Cache::forget(self::CACHE_KEY));
    }
}
