<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admissions\Models\AcademicSession;

class RefundPolicy extends Model
{
    public const FEE_TYPE_APPLICATION = 'application';

    public const FEE_TYPE_ADMISSION = 'admission';

    protected $fillable = [
        'academic_session_id',
        'fee_type',
        'name',
        'slabs',
        'deduction_cap',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'slabs' => 'array',
            'deduction_cap' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }
}
