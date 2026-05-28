<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class WhatsappProvider extends Model
{
    public const MODE_STUB = 'stub';

    public const MODE_TEST = 'test';

    public const MODE_LIVE = 'live';

    protected $fillable = [
        'code',
        'display_name',
        'mode',
        'is_active',
        'priority',
        'config_encrypted',
    ];

    protected $hidden = ['config_encrypted'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
        ];
    }

    protected function config(): Attribute
    {
        return Attribute::make(
            get: fn () => blank($this->config_encrypted) ? [] : decrypt($this->config_encrypted),
            set: fn (array $value) => ['config_encrypted' => encrypt($value)],
        );
    }

    public function isStub(): bool
    {
        return $this->mode === self::MODE_STUB;
    }
}
