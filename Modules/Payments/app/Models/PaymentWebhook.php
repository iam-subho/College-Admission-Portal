<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhook extends Model
{
    protected $fillable = [
        'payment_gateway_id',
        'event_type',
        'gateway_txn_id',
        'gateway_order_id',
        'idempotency_key',
        'payload',
        'signature_valid',
        'processed',
        'error',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'signature_valid' => 'boolean',
            'processed' => 'boolean',
        ];
    }

    public function gateway(): BelongsTo
    {
        return $this->belongsTo(PaymentGateway::class, 'payment_gateway_id');
    }
}
