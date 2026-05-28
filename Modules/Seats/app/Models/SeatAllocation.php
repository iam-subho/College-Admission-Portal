<?php

namespace Modules\Seats\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Admissions\Models\AdmissionRound;
use Modules\Admissions\Models\Application;
use Modules\Admissions\Models\ReservationCategory;
use Modules\Payments\Models\PaymentOrder;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class SeatAllocation extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'source', 'allotted_at', 'admitted_at', 'expires_at', 'audit_remark'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('seat_allocation');
    }

    public const STATUS_ALLOTTED = 'allotted';

    public const STATUS_ACCEPTED = 'accepted';

    public const STATUS_DECLINED = 'declined';

    public const STATUS_EXPIRED = 'expired';

    public const STATUS_ADMITTED = 'admitted';

    public const STATUS_WITHDRAWN = 'withdrawn';

    public const SOURCE_MERIT = 'merit';

    public const SOURCE_SPOT = 'spot';

    protected $fillable = [
        'admission_round_id',
        'application_id',
        'reservation_category_id',
        'status',
        'source',
        'rank_at_allotment',
        'category_rank_at_allotment',
        'allotted_at',
        'expires_at',
        'decided_at',
        'admitted_at',
        'withdrew_at',
        'admission_fee_order_id',
        'admitted_by_admin_id',
        'audit_remark',
    ];

    protected function casts(): array
    {
        return [
            'allotted_at' => 'datetime',
            'expires_at' => 'datetime',
            'decided_at' => 'datetime',
            'admitted_at' => 'datetime',
            'withdrew_at' => 'datetime',
        ];
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(AdmissionRound::class, 'admission_round_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ReservationCategory::class, 'reservation_category_id');
    }

    public function admissionFeeOrder(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class, 'admission_fee_order_id');
    }

    public function admittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admitted_by_admin_id');
    }

    public function acceptances(): HasMany
    {
        return $this->hasMany(SeatAcceptance::class);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [self::STATUS_ALLOTTED, self::STATUS_ACCEPTED], true);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast() && $this->status === self::STATUS_ALLOTTED;
    }
}
