<?php

namespace Modules\Notifications\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationLog extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_SENT = 'sent';

    public const STATUS_DELIVERED = 'delivered';

    public const STATUS_FAILED = 'failed';

    public const STATUS_STUB = 'stub';

    protected $fillable = [
        'event',
        'channel',
        'recipient',
        'user_id',
        'notification_template_id',
        'provider_code',
        'provider_message_id',
        'status',
        'rendered_body',
        'error',
        'context',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'context' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(NotificationTemplate::class, 'notification_template_id');
    }
}
