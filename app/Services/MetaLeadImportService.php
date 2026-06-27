<?php

namespace App\Services;

use App\Models\ExternalLeadEvent;
use Illuminate\Support\Facades\Http;

class MetaLeadImportService
{
    public function __construct(
        private readonly MetaLeadWebhookService $webhookService,
    ) {
    }

    public function importForm(string $formId, int $limit = 100): array
    {
        $token = config('spmm.meta_leads.page_access_token');

        if (blank($token)) {
            throw new \RuntimeException('META_LEADS_PAGE_ACCESS_TOKEN belum diisi.');
        }

        $version = trim((string) config('spmm.meta_leads.graph_version', 'v20.0'), '/');
        $url = "https://graph.facebook.com/{$version}/{$formId}/leads";
        $processed = 0;
        $duplicates = 0;
        $failed = 0;
        $seen = 0;

        while ($url && $seen < $limit) {
            $response = Http::timeout(20)
                ->retry(2, 500)
                ->get($url, [
                    'access_token' => $token,
                    'fields' => 'id,created_time',
                    'limit' => min(100, $limit - $seen),
                ]);

            if ($response->failed()) {
                throw new \RuntimeException('Gagal mengambil daftar lead Meta: '.$response->body());
            }

            $payload = $response->json();

            foreach (($payload['data'] ?? []) as $lead) {
                $leadgenId = (string) ($lead['id'] ?? '');

                if ($leadgenId === '') {
                    continue;
                }

                $event = $this->webhookService->handleLeadgen([
                    'field' => 'leadgen',
                    'value' => [
                        'leadgen_id' => $leadgenId,
                        'form_id' => $formId,
                        'created_time' => $lead['created_time'] ?? null,
                    ],
                ], [
                    'id' => config('spmm.meta_leads.page_id'),
                    'time' => now()->timestamp,
                    'source' => 'manual_import',
                ]);

                if ($event->status === 'processed') {
                    $processed++;
                } elseif ($event->status === 'duplicate') {
                    $duplicates++;
                } else {
                    $failed++;
                }

                $seen++;
            }

            $url = $payload['paging']['next'] ?? null;
        }

        return compact('processed', 'duplicates', 'failed', 'seen');
    }

    public function importLead(string $leadgenId): ExternalLeadEvent
    {
        return $this->webhookService->handleLeadgen([
            'field' => 'leadgen',
            'value' => ['leadgen_id' => $leadgenId],
        ], [
            'id' => config('spmm.meta_leads.page_id'),
            'time' => now()->timestamp,
            'source' => 'manual_import',
        ]);
    }
}
