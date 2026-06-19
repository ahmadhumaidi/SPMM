<?php

namespace App\Services;

use App\Models\Campus;
use App\Models\ClassTrack;
use App\Models\ExternalLeadEvent;
use App\Models\StudyProgram;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class MetaLeadWebhookService
{
    public function __construct(
        private readonly LeadRegistrationService $registration,
    ) {
    }

    public function handleLeadgen(array $change, array $entry): ExternalLeadEvent
    {
        $value = $change['value'] ?? [];
        $leadgenId = (string) ($value['leadgen_id'] ?? $value['leadgenId'] ?? '');

        if ($leadgenId === '') {
            return ExternalLeadEvent::query()->create([
                'provider' => 'meta',
                'external_id' => 'missing-'.Str::uuid(),
                'event_type' => 'leadgen',
                'status' => 'failed',
                'payload_json' => compact('change', 'entry'),
                'error_message' => 'Payload Meta tidak memiliki leadgen_id.',
                'received_at' => now(),
                'processed_at' => now(),
            ]);
        }

        $event = ExternalLeadEvent::query()->firstOrCreate(
            ['provider' => 'meta', 'external_id' => $leadgenId],
            [
                'event_type' => 'leadgen',
                'status' => 'pending',
                'payload_json' => compact('change', 'entry'),
                'received_at' => now(),
            ],
        );

        if ($event->wasRecentlyCreated === false && $event->lead_id) {
            $event->update(['status' => 'duplicate']);

            return $event;
        }

        try {
            $leadPayload = $this->fetchLeadPayload($leadgenId);
            $normalized = $this->normalizeLeadPayload($leadPayload, $value);
            $registrationData = $this->registrationData($normalized, $leadPayload, $value);
            $result = $this->registration->register($registrationData);

            $event->update([
                'status' => 'processed',
                'campus_id' => $registrationData['campus_id'],
                'study_program_id' => $registrationData['study_program_id'],
                'class_track_id' => $registrationData['class_track_id'],
                'lead_id' => $result['lead']->id,
                'normalized_payload_json' => [
                    'field_data' => $normalized,
                    'registration_data' => Arr::except($registrationData, ['referral_code']),
                    'meta' => Arr::only($leadPayload, ['id', 'created_time', 'form_id', 'ad_id', 'campaign_id']),
                ],
                'error_message' => null,
                'processed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $event->update([
                'status' => 'failed',
                'normalized_payload_json' => isset($normalized) ? ['field_data' => $normalized] : null,
                'error_message' => $exception->getMessage(),
                'processed_at' => now(),
            ]);
        }

        return $event;
    }

    private function fetchLeadPayload(string $leadgenId): array
    {
        $token = config('spmm.meta_leads.page_access_token');

        if (blank($token)) {
            throw new \RuntimeException('META_LEADS_PAGE_ACCESS_TOKEN belum diisi.');
        }

        $version = trim((string) config('spmm.meta_leads.graph_version', 'v20.0'), '/');
        $response = Http::timeout(15)
            ->retry(2, 500)
            ->get("https://graph.facebook.com/{$version}/{$leadgenId}", [
                'access_token' => $token,
                'fields' => 'id,created_time,field_data,form_id,ad_id,campaign_id',
            ]);

        if ($response->failed()) {
            throw new \RuntimeException('Gagal mengambil detail lead Meta: '.$response->body());
        }

        return $response->json();
    }

    private function normalizeLeadPayload(array $leadPayload, array $webhookValue): array
    {
        $fields = [];

        foreach (($leadPayload['field_data'] ?? []) as $field) {
            $key = Str::of((string) ($field['name'] ?? ''))
                ->lower()
                ->replace([' ', '-', '.', '/', '\\'], '_')
                ->replaceMatches('/_+/', '_')
                ->trim('_')
                ->toString();

            if ($key === '') {
                continue;
            }

            $fields[$key] = $field['values'][0] ?? null;
        }

        return array_merge($fields, [
            '_leadgen_id' => $leadPayload['id'] ?? $webhookValue['leadgen_id'] ?? null,
            '_form_id' => $leadPayload['form_id'] ?? $webhookValue['form_id'] ?? null,
            '_ad_id' => $leadPayload['ad_id'] ?? $webhookValue['ad_id'] ?? null,
            '_campaign_id' => $leadPayload['campaign_id'] ?? null,
            '_created_time' => $leadPayload['created_time'] ?? $webhookValue['created_time'] ?? null,
        ]);
    }

    private function registrationData(array $normalized, array $leadPayload, array $webhookValue): array
    {
        $campus = $this->resolveCampus($normalized);
        $studyProgram = $this->resolveStudyProgram($normalized, $campus);
        $classTrack = $this->resolveClassTrack($normalized, $campus);

        return [
            'campus_id' => $campus->id,
            'study_program_id' => $studyProgram->id,
            'class_track_id' => $classTrack->id,
            'full_name' => $this->firstValue($normalized, ['full_name', 'nama_lengkap', 'nama', 'name']) ?: 'Lead Meta '.$normalized['_leadgen_id'],
            'whatsapp_number' => $this->firstValue($normalized, ['whatsapp_number', 'nomor_whatsapp', 'no_whatsapp', 'no_wa', 'phone_number', 'phone', 'mobile_phone']),
            'email' => $this->firstValue($normalized, ['email', 'email_mahasiswa']) ?: 'lead-'.$normalized['_leadgen_id'].'@meta-lead.local',
            'origin_school' => $this->firstValue($normalized, ['origin_school', 'asal_sekolah', 'sekolah']),
            'graduation_year' => $this->firstValue($normalized, ['graduation_year', 'tahun_lulus']),
            'source_channel' => 'meta_lead_form',
            'source_detail' => collect([
                'Meta Lead Form',
                'form: '.($leadPayload['form_id'] ?? $webhookValue['form_id'] ?? '-'),
                'ad: '.($leadPayload['ad_id'] ?? $webhookValue['ad_id'] ?? '-'),
            ])->implode(' | '),
            'referral_code' => $this->firstValue($normalized, ['referral_code', 'kode_affiliator', 'affiliator_code']),
        ];
    }

    private function resolveCampus(array $normalized): Campus
    {
        $configured = config('spmm.meta_leads.default_campus_id');
        $campusValue = $this->firstValue($normalized, ['campus', 'kampus', 'kampus_tujuan', 'pilih_kampus']);

        if (filled($campusValue)) {
            $campus = Campus::query()
                ->where('name', 'like', '%'.$campusValue.'%')
                ->orWhere('slug', Str::slug($campusValue))
                ->orWhere('subdomain', Str::slug($campusValue))
                ->first();

            if ($campus) {
                return $campus;
            }
        }

        if (filled($configured)) {
            return Campus::query()->findOrFail((int) $configured);
        }

        return Campus::query()->orderBy('name')->firstOrFail();
    }

    private function resolveStudyProgram(array $normalized, Campus $campus): StudyProgram
    {
        $configured = config('spmm.meta_leads.default_study_program_id');
        $programValue = $this->firstValue($normalized, ['study_program', 'program_studi', 'prodi', 'jurusan']);

        if (filled($programValue)) {
            $program = StudyProgram::query()
                ->where('campus_id', $campus->id)
                ->where(function ($query) use ($programValue): void {
                    $query->where('name', 'like', '%'.$programValue.'%')
                        ->orWhereRaw("lower(degree_level || ' ' || name) like ?", ['%'.Str::lower($programValue).'%']);
                })
                ->first();

            if ($program) {
                return $program;
            }
        }

        if (filled($configured)) {
            return StudyProgram::query()
                ->where('campus_id', $campus->id)
                ->findOrFail((int) $configured);
        }

        return StudyProgram::query()->where('campus_id', $campus->id)->orderBy('degree_level')->orderBy('name')->firstOrFail();
    }

    private function resolveClassTrack(array $normalized, Campus $campus): ClassTrack
    {
        $configured = config('spmm.meta_leads.default_class_track_id');
        $trackValue = $this->firstValue($normalized, ['class_track', 'program_perkuliahan', 'program_kuliah', 'kelas']);

        if (filled($trackValue)) {
            $track = ClassTrack::query()
                ->where('campus_id', $campus->id)
                ->where('name', 'like', '%'.$trackValue.'%')
                ->first();

            if ($track) {
                return $track;
            }
        }

        if (filled($configured)) {
            return ClassTrack::query()
                ->where('campus_id', $campus->id)
                ->findOrFail((int) $configured);
        }

        return ClassTrack::query()->where('campus_id', $campus->id)->orderBy('name')->firstOrFail();
    }

    private function firstValue(array $data, array $keys): mixed
    {
        foreach ($keys as $key) {
            if (filled($data[$key] ?? null)) {
                return $data[$key];
            }
        }

        return null;
    }
}
