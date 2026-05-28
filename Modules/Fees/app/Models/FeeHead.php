<?php

namespace Modules\Fees\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Fees\Database\Factories\FeeHeadFactory;

class FeeHead extends Model
{
    use HasFactory;

    public const CAT_APPLICATION = 'application';

    public const CAT_TUITION = 'tuition';

    public const CAT_OTHER = 'other';

    protected $fillable = [
        'code',
        'name',
        'category',
        'is_refundable',
        'ordering',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_refundable' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function newFactory(): FeeHeadFactory
    {
        return FeeHeadFactory::new();
    }
}
