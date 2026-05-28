<?php

namespace Modules\Students\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Students\Database\Factories\StudentAcademicRecordFactory;

class StudentAcademicRecord extends Model
{
    use HasFactory;

    public const LEVEL_10TH = '10th';

    public const LEVEL_12TH = '12th';

    public const LEVEL_UG = 'ug';

    protected $fillable = [
        'student_id',
        'level',
        'board',
        'passing_year',
        'percentage',
        'aggregate_best5_pct',
        'cgpa',
        'full_marks',
        'obtained_marks',
        'school_name',
        'school_code',
        'roll_number',
        'stream',
        'medium',
        'subjects',
    ];

    protected function casts(): array
    {
        return [
            'passing_year' => 'integer',
            'percentage' => 'decimal:2',
            'aggregate_best5_pct' => 'decimal:2',
            'cgpa' => 'decimal:2',
            'full_marks' => 'decimal:2',
            'obtained_marks' => 'decimal:2',
            'subjects' => 'array',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    protected static function newFactory(): StudentAcademicRecordFactory
    {
        return StudentAcademicRecordFactory::new();
    }
}
