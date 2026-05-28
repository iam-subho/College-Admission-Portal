<?php

namespace Modules\Merit\Models;

use Illuminate\Database\Eloquent\Model;

class MeritFormula extends Model
{
    public const TIE_BREAK_TEST_MARKS = 'test_marks';

    public const TIE_BREAK_BOARD_PCT = 'board_pct';

    public const TIE_BREAK_DOB = 'dob';

    public const TIE_BREAK_SUBMITTED_AT = 'submitted_at';

    protected $fillable = [
        'name',
        'description',
        'test_weight',
        'marks_weight',
        'tie_breakers',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'test_weight' => 'decimal:2',
            'marks_weight' => 'decimal:2',
            'tie_breakers' => 'array',
            'is_default' => 'boolean',
        ];
    }
}
