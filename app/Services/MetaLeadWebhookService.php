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

    public function handleLeadgen(array $change, array $entry, bool $reprocessExisting = false): ExternalLeadEvent
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

        if ($event->wasRecentlyCreated === false && $event->lead_id && ! $reprocessExisting) {
            $event->update(['status' => 'duplicate']);

            return $event;
        }

        try {
            $leadPayload = $this->fetchLeadPayload($leadgenId);
            $normalized = $this->normalizeLeadPayload($leadPayload, $value);
            $registrationData = $this->registrationData($normalized, $leadPayload, $value);
            $lead = $event->lead_id && $reprocessExisting
                ? $this->updateExistingLead($event, $registrationData)
                : $this->registration->register($registrationData)['lead'];

            $event->update([
                'status' => 'processed',
                'campus_id' => $registrationData['campus_id'],
                'study_program_id' => $registrationData['study_program_id'],
                'class_track_id' => $registrationData['class_track_id'],
                'lead_id' => $lead->id,
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

    private function updateExistingLead(ExternalLeadEvent $event, array $registrationData): \App\Models\Lead
    {
        $lead = $event->lead()->firstOrFail();

        $lead->update([
            'campus_id' => $registrationData['campus_id'],
            'study_program_id' => $registrationData['study_program_id'],
            'class_track_id' => $registrationData['class_track_id'],
            'source_channel' => $registrationData['source_channel'],
            'source_detail' => $registrationData['source_detail'],
        ]);

        return $lead;
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
        $campusValue = $this->firstValue($normalized, ['campus', 'kampus', 'kampus_tujuan', 'pilih_kampus', 'conditional_question_1']);

        if (filled($campusValue)) {
            $campus = $this->findCampusByMetaValue((string) $campusValue);

            if ($campus) {
                return $campus;
            }
        }

        if (filled($configured)) {
            return Campus::query()->findOrFail((int) $configured);
        }

        return Campus::query()->orderBy('name')->firstOrFail();
    }

    private function findCampusByMetaValue(string $campusValue): ?Campus
    {
        $needle = $this->normalizeComparableText($campusValue);
        $slug = Str::slug($campusValue);

        return Campus::query()
            ->orderByRaw('length(name) desc')
            ->get()
            ->first(function (Campus $campus) use ($needle, $slug): bool {
                $name = $this->normalizeComparableText((string) $campus->name);
                $compactNeedle = str_replace(' ', '', $needle);
                $compactName = str_replace(' ', '', $name);
                $campusSlug = (string) $campus->slug;
                $subdomain = (string) $campus->subdomain;
                $sharedTokens = array_intersect(
                    $this->importantTokens($needle),
                    $this->importantTokens($name),
                );

                return $needle === $name
                    || ($name !== '' && str_contains($needle, $name))
                    || ($needle !== '' && str_contains($name, $needle))
                    || ($compactName !== '' && str_contains($compactNeedle, $compactName))
                    || ($compactNeedle !== '' && str_contains($compactName, $compactNeedle))
                    || count($sharedTokens) >= 2
                    || ($campusSlug !== '' && ($slug === $campusSlug || str_contains($slug, $campusSlug)))
                    || ($subdomain !== '' && ($slug === $subdomain || str_contains($slug, $subdomain)));
            });
    }

    private function resolveStudyProgram(array $normalized, Campus $campus): StudyProgram
    {
        $configured = config('spmm.meta_leads.default_study_program_id');
        $programValue = $this->firstValue($normalized, ['study_program', 'program_studi', 'prodi', 'jurusan', 'conditional_question_2']);

        if (filled($programValue)) {
            $program = $this->findStudyProgramByMetaValue((string) $programValue, $campus);

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

    private function findStudyProgramByMetaValue(string $programValue, Campus $campus): ?StudyProgram
    {
        $needle = $this->normalizeComparableText($programValue);
        $withoutDegree = $this->normalizeComparableText((string) preg_replace('/^(d3|d4|s1|s2|s3|profesi)\s*[-:]?\s*/i', '', $programValue));

        return StudyProgram::query()
            ->where('campus_id', $campus->id)
            ->orderBy('degree_level')
            ->orderBy('name')
            ->get()
            ->first(function (StudyProgram $program) use ($needle, $withoutDegree): bool {
                $name = $this->normalizeComparableText((string) $program->name);
                $degreeAndName = $this->normalizeComparableText(trim((string) $program->degree_level.' '.(string) $program->name));

                return $needle === $name
                    || $needle === $degreeAndName
                    || ($needle !== '' && str_contains($degreeAndName, $needle))
                    || ($name !== '' && str_contains($needle, $name))
                    || ($withoutDegree !== '' && str_contains($name, $withoutDegree))
                    || ($withoutDegree !== '' && str_contains($withoutDegree, $name))
                    || ($withoutDegree !== '' && str_contains($degreeAndName, $withoutDegree));
            });
    }

    private function resolveClassTrack(array $normalized, Campus $campus): ClassTrack
    {
        $configured = config('spmm.meta_leads.default_class_track_id');
        $trackValue = $this->firstValue($normalized, ['class_track', 'program_perkuliahan', 'program_kuliah', 'kelas', 'conditional_question_3']);

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

    private function normalizeComparableText(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->replace(['.', ',', '-', '/', '\\', '&'], ' ')
            ->replaceMatches('/\s+/', ' ')
            ->trim()
            ->toString();
    }

    /**
     * Keep only words that help identify a campus. This catches cases like
     * "Universitas Kristen Cipta Wacana Malang" -> "Cipta Wacana University".
     *
     * @return array<int, string>
     */
    private function importantTokens(string $value): array
    {
        $stopWords = [
            'universitas',
            'university',
            'kampus',
            'college',
            'sekolah',
            'tinggi',
            'kristen',
            'malang',
            'bogor',
            'bandung',
            'jakarta',
            'surabaya',
        ];

        return collect(explode(' ', $value))
            ->map(fn (string $token): string => trim($token))
            ->filter(fn (string $token): bool => strlen($token) >= 3 && ! in_array($token, $stopWords, true))
            ->unique()
            ->values()
            ->all();
    }
}
