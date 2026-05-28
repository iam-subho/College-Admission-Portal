<?php

namespace Modules\Seats\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Admissions\Models\AdmissionRound;
use Modules\Admissions\Models\Application;
use Modules\Payments\Models\PaymentOrder;

class SeatAcceptance extends Model
{
    public const ACTION_ACCEPT = 'accept';

    public const ACTION_DECLINE = 'decline';

    public const ACTION_EXPIRE = 'expire';

    public const ACTION_WITHDRAW = 'withdraw';

    public const ACTION_SPOT_ALLOT = 'spot_allot';

    protected $fillable = [
        'seat_allocation_id',
        'application_id',
        'admission_round_id',
        'action',
        'reason',
        'decided_by_user_id',
        'admission_fee_order_id',
        'decided_at',
    ];

    protected function casts(): array
    {
        return [
            'decided_at' => 'datetime',
        ];
    }

    public function allocation(): BelongsTo
    {
        return $this->belongsTo(SeatAllocation::class, 'seat_allocation_id');
    }

    public function application(): BelongsTo
    {
        return $this->belongsTo(Application::class);
    }

    public function round(): BelongsTo
    {
        return $this->belongsTo(AdmissionRound::class, 'admission_round_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by_user_id');
    }

    public function admissionFeeOrder(): BelongsTo
    {
        return $this->belongsTo(PaymentOrder::class, 'admission_fee_order_id');
    }
}
