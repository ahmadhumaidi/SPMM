<?php

namespace App\Http\Controllers;

use App\Services\Payment\PaymentWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentWebhookController extends Controller
{
    public function __invoke(string $provider, Request $request, PaymentWebhookService $webhooks): JsonResponse
    {
        $event = $webhooks->handle($provider, $request);

        return response()->json([
            'processed' => true,
            'event_id' => $event->id,
        ]);
    }
}
