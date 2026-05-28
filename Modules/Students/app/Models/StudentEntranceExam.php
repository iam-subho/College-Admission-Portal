<?php

namespace Modules\Students\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEntranceExam extends Model
{
    protected $fillable = [
        'student_id',
        'exam_name',
        'roll_number',
        'score',
        'exam_year',
    ];

    protected function casts(): array
    {
        return [
            'exam_year' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
