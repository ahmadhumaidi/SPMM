<?php

namespace App\Http\Controllers;

use App\Services\MetaLeadWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MetaLeadWebhookController extends Controller
{
    public function verify(Request $request): Response|JsonResponse
    {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        if ($mode === 'subscribe' && filled($challenge) && hash_equals((string) config('spmm.meta_leads.verify_token'), (string) $token)) {
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response()->json(['message' => 'Meta webhook verification failed.'], 403);
    }

    public function store(Request $request, MetaLeadWebhookService $service): JsonResponse
    {
        $payload = $request->all();
        $processed = 0;
        $failed = 0;

        foreach (($payload['entry'] ?? []) as $entry) {
            foreach (($entry['changes'] ?? []) as $change) {
                if (($change['field'] ?? null) !== 'leadgen') {
                    continue;
                }

                $event = $service->handleLeadgen($change, $entry);

                if ($event->status === 'processed' || $event->status === 'duplicate') {
                    $processed++;
                } else {
                    $failed++;
                }
            }
        }

        if ($processed === 0 && $failed === 0) {
            Log::info('Meta lead webhook received without leadgen changes.', ['payload' => $payload]);
        }

        return response()->json([
            'ok' => true,
            'processed' => $processed,
            'failed' => $failed,
        ]);
    }
}
