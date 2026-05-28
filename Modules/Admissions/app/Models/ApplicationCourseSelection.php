<?php

namespace Modules\Admissions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Academics\Models\ProgrammeCoursePool;

class ApplicationCourseSelection extends Model
{
    protected $fillable = [
        'application_id',
        'pool_id',
        'category',
    ];

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function pool(): BelongsTo
    {
        return $this->belongsTo(ProgrammeCoursePool::class, 'pool_id');
    }
}
