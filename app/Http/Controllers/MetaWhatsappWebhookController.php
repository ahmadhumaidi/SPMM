<?php

namespace App\Http\Controllers;

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

    public function store(Request $request): JsonResponse
    {
        Log::info('Meta WhatsApp webhook received.', [
            'payload' => $request->all(),
        ]);

        return response()->json(['ok' => true]);
    }
}
