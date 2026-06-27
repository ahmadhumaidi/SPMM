<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\LeadProspectEvent;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MetaProspectEventService
{
    public function buildEvent(Lead $lead, string $prospectStatus, ?int $userId = null): array
    {
        $eventName = 'KampusMediaProspect'.Str::studly($prospectStatus);

        return [
            'event_name' => $eventName,
            'event_time' => now()->timestamp,
            'action_source' => config('spmm.meta_conversions.action_source', 'system_generated'),
            'event_id' => 'lead-'.$lead->id.'-'.$prospectStatus.'-'.now()->timestamp,
            'user_data' => array_filter([
                'em' => $this->hash($lead->email),
                'ph' => $this->hash($lead->whatsapp_number),
            ]),
            'custom_data' => [
                'lead_id' => $lead->id,
                'prospect_status' => $prospectStatus,
                'campus' => $lead->campus?->name,
                'study_program' => trim(($lead->studyProgram?->degree_level ? $lead->studyProgram->degree_level.' ' : '').($lead->studyProgram?->name ?? '')),
                'source_channel' => $lead->source_channel,
                'source_detail' => $lead->source_detail,
                'updated_by_user_id' => $userId,
            ],
        ];
    }

    public function send(LeadProspectEvent $event): void
    {
        $pixelId = config('spmm.meta_conversions.pixel_id');
        $accessToken = config('spmm.meta_conversions.access_token');

        if (blank($pixelId) || blank($accessToken)) {
            $event->update([
                'meta_status' => 'pending_config',
                'meta_error_message' => 'META_CAPI_PIXEL_ID atau META_CAPI_ACCESS_TOKEN belum diisi.',
            ]);

            return;
        }

        $version = trim((string) config('spmm.meta_conversions.graph_version', 'v20.0'), '/');
        $payload = [
            'data' => [$event->meta_payload_json],
            'access_token' => $accessToken,
        ];

        if (filled(config('spmm.meta_conversions.test_event_code'))) {
            $payload['test_event_code'] = config('spmm.meta_conversions.test_event_code');
        }

        $response = Http::timeout(10)
            ->retry(1, 500)
            ->post("https://graph.facebook.com/{$version}/{$pixelId}/events", $payload);

        if ($response->failed()) {
            $event->update([
                'meta_status' => 'failed',
                'meta_error_message' => $response->body(),
            ]);

            return;
        }

        $event->update([
            'meta_status' => 'sent',
            'meta_error_message' => null,
            'sent_at' => now(),
        ]);
    }

    private function hash(?string $value): ?string
    {
        $raw = Str::lower((string) $value);
        $normalized = str_contains($raw, '@')
            ? preg_replace('/\s+/', '', $raw)
            : preg_replace('/\D+/', '', $raw);

        if ($normalized === '') {
            return null;
        }

        return hash('sha256', $normalized);
    }
}
