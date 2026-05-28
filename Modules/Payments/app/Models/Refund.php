<?php

namespace Modules\Payments\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\WithdrawalRequest;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Refund extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'amount', 'completed_at', 'offline_reference', 'policy_slab'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('refund');
    }

    public const STATUS_PENDING = 'pending';

    public const STATUS_INITIATED = 'initiated';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const METHOD_GATEWAY = 'gateway';

    public const METHOD_OFFLINE = 'offline';

    protected $fillable = [
        'payment_order_id',
        'payment_transaction_id',
        'application_id',
        'withdrawal_request_id',
        'amount',
        'deduction_amount',
        'policy_slab',
        'status',
        'refund_method',
        'offline_reference',
        'gateway_refund_id',
        'gateway_payload',
        'initiated_by',
        'reason',
        'approved_at',
        'approved_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'deduction_amount' => 'decimal:2',
            'gateway_payload' => 'array',
            'approved_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class, 'payment_order_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(PaymentTransaction::class, 'payment_transaction_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function withdrawalRequest(): BelongsTo
    {
        return $this->belongsTo(WithdrawalRequest::class);
    }

    public function initiatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiated_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
