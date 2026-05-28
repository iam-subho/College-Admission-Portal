<?php

namespace Modules\Academics\Models;

use Illuminate\Database\Eloquent\Model;

class AcademicSubject extends Model
{
    public const LEVEL_10TH = '10th';

    public const LEVEL_12TH = '12th';

    public const LEVEL_UG = 'ug';

    public const STREAM_SCIENCE = 'Science';

    public const STREAM_COMMERCE = 'Commerce';

    public const STREAM_ARTS = 'Arts';

    public const STREAM_LANGUAGE = 'Language';

    public const STREAM_COMMON = 'Common';

    public const STREAM_VOCATIONAL = 'Vocational';

    protected $fillable = [
        'code',
        'name',
        'level',
        'stream',
        'is_language',
        'is_active',
        'ordering',
    ];

    protected function casts(): array
    {
        return [
            'is_language' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForLevel($query, string $level)
    {
        return $query->where('level', $level);
    }
}
