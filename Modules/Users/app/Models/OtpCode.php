<?php

namespace Modules\Users\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpCode extends Model
{
    use HasFactory;

    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_EMAIL = 'email';

    public const PURPOSE_REGISTRATION = 'registration';

    public const PURPOSE_LOGIN = 'login';

    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    public const MAX_ATTEMPTS = 5;

    protected $fillable = [
        'user_id',
        'channel',
        'purpose',
        'recipient',
        'code_hash',
        'expires_at',
        'attempts',
        'used_at',
        'ip',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    public function isExhausted(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }
}
