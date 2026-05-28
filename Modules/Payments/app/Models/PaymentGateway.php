<?php

namespace Modules\Payments\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentGateway extends Model
{
    public const MODE_STUB = 'stub';

    public const MODE_TEST = 'test';

    public const MODE_LIVE = 'live';

    protected $fillable = [
        'code',
        'display_name',
        'is_active',
        'mode',
        'priority',
        'config_encrypted',
        'convenience_fee_rule',
        'logo_url',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
        ];
    }

    protected $hidden = ['config_encrypted'];

    /**
     * Encrypted JSON accessor. Set with an array → encrypted on the way in.
     * Read returns a decrypted array (empty array if blank).
     */
    protected function config(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (blank($this->config_encrypted)) {
                    return [];
                }

                try {
                    return decrypt($this->config_encrypted);
                } catch (\Throwable) {
                    return [];
                }
            },
            set: fn (array $value) => ['config_encrypted' => encrypt($value)],
        );
    }

    public function orders(): HasMany
    {
        return $this->hasMany(PaymentOrder::class);
    }

    public function isStub(): bool
    {
        return $this->mode === self::MODE_STUB;
    }
}
