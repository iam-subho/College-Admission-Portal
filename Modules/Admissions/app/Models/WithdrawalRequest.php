<?php

namespace Modules\Admissions\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Payments\Models\Refund;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class WithdrawalRequest extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'reason', 'admin_remark', 'decided_at'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('withdrawal');
    }

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'application_id',
        'requested_by',
        'reason',
        'status',
        'requested_at',
        'decided_at',
        'decided_by',
        'admin_remark',
        'estimated_refund',
        'estimated_deduction',
        'estimated_slab',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'decided_at' => 'datetime',
            'estimated_refund' => 'decimal:2',
            'estimated_deduction' => 'decimal:2',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    public function refund(): HasOne
    {
        return $this->hasOne(Refund::class, 'withdrawal_request_id');
    }
}
