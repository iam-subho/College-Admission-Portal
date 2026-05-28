<?php

namespace Modules\Payments\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Payments\Actions\ProcessWebhookAction;

class WebhookController extends Controller
{
    public function __invoke(Request $request, string $gateway, ProcessWebhookAction $action): JsonResponse
    {
        $webhook = $action->execute($gateway, $request);

        return response()->json([
            'ok' => $webhook->signature_valid,
            'processed' => $webhook->processed,
        ]);
    }
}
