<?php

namespace App\Services;

use App\Models\SiakadStudentSyncEvent;
use App\Models\StudentNumber;
use Illuminate\Support\Facades\Http;

class SiakadStudentSyncService
{
    public function syncActivation(StudentNumber $studentNumber): SiakadStudentSyncEvent
    {
        $studentNumber->loadMissing(['lead.studentProfile']);

        $event = SiakadStudentSyncEvent::query()->create([
            'lead_id' => $studentNumber->lead_id,
            'student_number_id' => $studentNumber->id,
            'payload_json' => $this->buildPayload($studentNumber),
            'status' => 'pending',
        ]);

        $this->send($event);

        return $event->fresh();
    }

    public function buildPayload(StudentNumber $studentNumber): array
    {
        $lead = $studentNumber->lead;

        return [
            'student_uuid' => $studentNumber->uuid,
            'lead_id' => $lead->id,
            'campus_id' => $lead->campus_id,
            'nim' => $studentNumber->nim,
            'name' => $lead->full_name,
            'email' => $lead->email,
            'whatsapp_number' => $lead->whatsapp_number,
            'study_program_id' => $lead->study_program_id,
            'class_track_id' => $lead->class_track_id,
            'student_status' => $lead->enrollment_status?->value ?? (string) $lead->enrollment_status,
            'verified_at' => optional($lead->studentProfile?->verified_at)->toIso8601String(),
        ];
    }

    public function send(SiakadStudentSyncEvent $event): void
    {
        $baseUrl = config('spmm.siakad_integration.base_url');
        $token = config('spmm.siakad_integration.api_token');

        if (blank($baseUrl) || blank($token)) {
            $event->update([
                'status' => 'pending_config',
                'error_message' => 'SIAKAD_INTEGRATION_BASE_URL atau SIAKAD_INTEGRATION_API_TOKEN belum diisi.',
            ]);

            return;
        }

        $response = Http::withToken($token)
            ->timeout(10)
            ->retry(1, 500)
            ->post(rtrim($baseUrl, '/').'/api/integrations/students', $event->payload_json);

        if ($response->failed()) {
            $event->update([
                'status' => 'failed',
                'error_message' => $response->body(),
            ]);

            return;
        }

        $event->update([
            'status' => 'sent',
            'error_message' => null,
            'sent_at' => now(),
        ]);
    }
}
