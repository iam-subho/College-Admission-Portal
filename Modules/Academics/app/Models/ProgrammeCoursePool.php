<?php

namespace Modules\Academics\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProgrammeCoursePool extends Model
{
    public const CATEGORIES = [
        'major' => 'Major (Core Discipline)',
        'minor' => 'Minor',
        'aec' => 'Ability Enhancement (AEC)',
        'sec' => 'Skill Enhancement (SEC)',
        'vac' => 'Value-Added Course (VAC)',
        'mdc' => 'Multi-Disciplinary (MDC)',
        'internship' => 'Internship / Field Project',
        'research' => 'Research Project',
    ];

    protected $fillable = [
        'program_id',
        'category',
        'course_code',
        'course_name',
        'credits',
        'is_default',
        'is_active',
        'ordering',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'is_default' => 'boolean',
            'is_active' => 'boolean',
            'credits' => 'integer',
            'ordering' => 'integer',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public static function categoryLabel(string $code): string
    {
        return self::CATEGORIES[$code] ?? $code;
    }
}
