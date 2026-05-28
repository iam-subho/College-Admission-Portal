<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admissions\Models\Application;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class PaymentOrder extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'paid_at', 'total', 'gateway_order_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('payment_order');
    }

    public const PURPOSE_APPLICATION_FEE = 'application_fee';

    public const PURPOSE_ADMISSION_FEE = 'admission_fee';

    public const STATUS_INITIATED = 'initiated';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PAID = 'paid';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REFUNDED = 'refunded';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'application_id',
        'payment_gateway_id',
        'order_number',
        'purpose',
        'amount',
        'convenience_fee',
        'gst',
        'total',
        'currency',
        'status',
        'gateway_order_id',
        'gateway_payload',
        'initiated_at',
        'paid_at',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'convenience_fee' => 'decimal:2',
            'gst' => 'decimal:2',
            'total' => 'decimal:2',
            'gateway_payload' => 'array',
            'initiated_at' => 'datetime',
            'paid_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }
}
