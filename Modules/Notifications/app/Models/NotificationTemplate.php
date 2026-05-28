<?php

namespace Modules\Notifications\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class NotificationTemplate extends Model
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['event', 'channel', 'subject', 'body', 'is_active', 'dlt_template_id', 'whatsapp_template_name'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->useLogName('notification_template');
    }

    public const CHANNEL_SMS = 'sms';

    public const CHANNEL_EMAIL = 'email';

    public const CHANNEL_WHATSAPP = 'whatsapp';

    public const CHANNELS = [self::CHANNEL_SMS, self::CHANNEL_EMAIL, self::CHANNEL_WHATSAPP];

    protected $fillable = [
        'event',
        'channel',
        'subject',
        'body',
        'variables',
        'dlt_template_id',
        'whatsapp_template_name',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Render the body with the given context. Replaces {{key}} with value;
     * unknown keys are left as-is so admin can spot bad bindings.
     */
    public function render(array $context): string
    {
        $out = $this->body;
        foreach ($context as $key => $value) {
            $out = str_replace('{{'.$key.'}}', (string) $value, $out);
        }

        return $out;
    }

    public function renderSubject(array $context): ?string
    {
        if (! $this->subject) {
            return null;
        }
        $out = $this->subject;
        foreach ($context as $key => $value) {
            $out = str_replace('{{'.$key.'}}', (string) $value, $out);
        }

        return $out;
    }
}
