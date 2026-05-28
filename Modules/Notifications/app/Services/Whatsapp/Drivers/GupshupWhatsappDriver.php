<?php

namespace Modules\Notifications\Services\Whatsapp\Drivers;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Notifications\Contracts\SendResult;
use Modules\Notifications\Contracts\WhatsappDriverContract;
use Modules\Notifications\Models\WhatsappProvider;

/**
 * Gupshup WhatsApp Business driver. Three modes:
 *   - stub: synthetic message id, log-only.
 *   - test/live: hits api.gupshup.io /sm/api/v1/template/msg.
 */
class GupshupWhatsappDriver implements WhatsappDriverContract
{
    public function __construct(protected WhatsappProvider $provider) {}

    public function code(): string
    {
        return 'gupshup';
    }

    public function sendTemplate(string $toE164, string $templateName, array $vars, string $renderedBody): SendResult
    {
        if ($this->provider->isStub()) {
            $id = 'gupshup_stub_'.Str::random(10);
            Log::info("[Gupshup STUB] to={$toE164} template={$templateName} body={$renderedBody}");

            return SendResult::ok($id, ['stub' => true]);
        }

        $apiKey = $this->provider->config['api_key'] ?? null;
        $source = $this->provider->config['source_number'] ?? null;
        $appName = $this->provider->config['app_name'] ?? null;

        if (! $apiKey || ! $source || ! $appName) {
            return SendResult::fail('Gupshup api_key / source_number / app_name not configured.');
        }

        $phone = preg_replace('/^\+/', '', $toE164);

        try {
            $resp = Http::asForm()->withHeaders([
                'apikey' => $apiKey,
                'accept' => 'application/json',
            ])->timeout(15)->post('https://api.gupshup.io/sm/api/v1/template/msg', [
                'source' => $source,
                'destination' => $phone,
                'template' => json_encode([
                    'id' => $templateName,
                    'params' => array_values($vars),
                ]),
                'src.name' => $appName,
            ]);

            $data = $resp->json();
            if (! $resp->successful() || ($data['status'] ?? '') !== 'submitted') {
                return SendResult::fail($data['message'] ?? 'Gupshup send failed.', $data ?? []);
            }

            return SendResult::ok($data['messageId'] ?? null, $data ?? []);
        } catch (\Throwable $e) {
            return SendResult::fail('Gupshup exception: '.$e->getMessage());
        }
    }
}
