<?php

namespace App\Http\Controllers;

use App\Services\MetaCtwaWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MetaWhatsappWebhookController extends Controller
{
    public function verify(Request $request): Response|JsonResponse
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        if ($mode === 'subscribe' && filled($challenge) && hash_equals((string) config('spmm.whatsapp.meta_verify_token'), (string) $token)) {
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response()->json(['message' => 'WhatsApp webhook verification failed.'], 403);
    }

    public function store(Request $request, MetaCtwaWebhookService $service): JsonResponse
    {
        $payload = $request->all();
        $processed = 0;

        foreach (($payload['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                if (($change['field'] ?? null) !== 'messages') {
                    continue;
                }

                $processed += count($service->handleMessagesChange($change, $entry));
            }
        }

        if ($processed === 0) {
            Log::info('Meta WhatsApp webhook received without CTWA referral messages.', [
                'payload' => $payload,
            ]);
        }

        return response()->json(['ok' => true, 'processed' => $processed]);
    }
}
