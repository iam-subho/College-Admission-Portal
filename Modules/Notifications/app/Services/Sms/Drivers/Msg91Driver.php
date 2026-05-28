<?php

namespace Modules\Notifications\Services\Sms\Drivers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Notifications\Contracts\SendResult;
use Modules\Notifications\Contracts\SmsDriverContract;
use Modules\Notifications\Models\SmsProvider;

/**
 * MSG91 transactional SMS driver. Three modes:
 *   - stub: returns a synthetic message id, logs to laravel.log, no HTTP call.
 *   - test/live: hits api.msg91.com /api/v5/flow/.
 */
class Msg91Driver implements SmsDriverContract
{
    public function __construct(protected SmsProvider $provider) {}

    public function code(): string
    {
        return 'msg91';
    }

    public function send(string $toE164, string $body, ?string $dltTemplateId = null): SendResult
    {
        if ($this->provider->isStub()) {
            $id = 'msg91_stub_'.Str::random(10);
            Log::info("[MSG91 STUB] to={$toE164} dlt={$dltTemplateId} body={$body}");

            return SendResult::ok($id, ['stub' => true]);
        }

        $authKey = $this->provider->config['auth_key'] ?? null;
        $senderId = $this->provider->config['sender_id'] ?? null;
        $flowId = $this->provider->config['flow_id'] ?? null;

        if (! $authKey || ! $senderId) {
            return SendResult::fail('MSG91 auth_key/sender_id not configured.');
        }
        if (! $dltTemplateId && ! $flowId) {
            return SendResult::fail('MSG91 requires either dlt_template_id on the template or flow_id in provider config.');
        }

        $phone = preg_replace('/^\+/', '', $toE164);

        try {
            $resp = Http::withHeaders([
                'authkey' => $authKey,
                'accept' => 'application/json',
            ])->timeout(15)->post('https://api.msg91.com/api/v5/flow/', [
                'flow_id' => $flowId ?? $dltTemplateId,
                'sender' => $senderId,
                'short_url' => '1',
                'recipients' => [
                    ['mobiles' => $phone, 'body' => $body],
                ],
            ]);

            $data = $resp->json();
            if (! $resp->successful() || ($data['type'] ?? null) === 'error') {
                return SendResult::fail($data['message'] ?? 'MSG91 send failed.', $data ?? []);
            }

            return SendResult::ok($data['request_id'] ?? null, $data ?? []);
        } catch (\Throwable $e) {
            return SendResult::fail('MSG91 exception: '.$e->getMessage());
        }
    }
}
