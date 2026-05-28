<?php

namespace Modules\Documents\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DigilockerLink extends Model
{
    protected $fillable = [
        'user_id',
        'digilocker_user_id',
        'access_token_enc',
        'refresh_token_enc',
        'linked_at',
        'revoked_at',
    ];

    protected function casts(): array
    {
        return [
            'linked_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    protected function accessToken(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->access_token_enc ? decrypt($this->access_token_enc) : null,
            set: fn (?string $v) => ['access_token_enc' => $v ? encrypt($v) : null],
        );
    }

    protected function refreshToken(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->refresh_token_enc ? decrypt($this->refresh_token_enc) : null,
            set: fn (?string $v) => ['refresh_token_enc' => $v ? encrypt($v) : null],
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isActive(): bool
    {
        return $this->linked_at !== null && $this->revoked_at === null;
    }
}
