<?php

namespace Modules\Users\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DpdpConsent extends Model
{
    use HasFactory;

    public const SCOPE_REGISTRATION = 'registration';

    public const SCOPE_PROFILE_LOCK = 'profile_lock';

    public const SCOPE_PAYMENT = 'payment';

    public const SCOPE_DOCUMENT_UPLOAD = 'document_upload';

    public const SCOPE_DIGILOCKER = 'digilocker';

    protected $fillable = [
        'user_id',
        'scope',
        'version',
        'accepted_at',
        'ip',
        'user_agent',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
