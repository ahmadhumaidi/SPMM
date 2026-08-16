<?php

namespace App\Services;

use App\Enums\ActivityType;
use App\Enums\EnrollmentStatus;
use App\Enums\LeadStatus;
use App\Enums\PaymentStatus;
use App\Models\Campus;
use App\Models\ClassTrack;
use App\Models\ExternalLeadEvent;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\StudyProgram;
use App\Support\PhoneNumber;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class MetaCtwaWebhookService
{
    /**
     * @return array<int, ExternalLeadEvent>
     */
    public function handleMessagesChange(array $change, array $entry): array
    {
        $value = $change['value'] ?? [];
        $contacts = collect($value['contacts'] ?? []);

        return collect($value['messages'] ?? [])
            ->filter(fn (array $message): bool => ($message['referral']['source_type'] ?? null) === 'ad')
            ->map(fn (array $message): ExternalLeadEvent => $this->handleReferralMessage($message, $contacts, $entry))
            ->all();
    }

    private function handleReferralMessage(array $message, Collection $contacts, array $entry): ExternalLeadEvent
    {
        $messageId = (string) ($message['id'] ?? '');

        if ($messageId === '') {
            return ExternalLeadEvent::query()->create([
                'provider' => 'meta',
                'external_id' => 'missing-'.Str::uuid(),
                'event_type' => 'ctwa',
                'status' => 'failed',
                'payload_json' => compact('message', 'entry'),
                'error_message' => 'Payload CTWA tidak memiliki message id.',
                'received_at' => now(),
                'processed_at' => now(),
            ]);
        }

        $event = ExternalLeadEvent::query()->firstOrCreate(
            ['provider' => 'meta', 'external_id' => $messageId],
            [
                'event_type' => 'ctwa',
                'status' => 'pending',
                'payload_json' => compact('message', 'entry'),
                'received_at' => now(),
            ],
        );

        if ($event->wasRecentlyCreated === false) {
            return $event;
        }

        try {
            $lead = $this->resolveOrCreateLead($message, $contacts, $event, $messageId);

            $event->update([
                'status' => 'processed',
                'campus_id' => $lead->campus_id,
                'study_program_id' => $lead->study_program_id,
                'class_track_id' => $lead->class_track_id,
                'lead_id' => $lead->id,
                'error_message' => null,
                'processed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            $event->update([
                'status' => 'failed',
                'error_message' => $exception->getMessage(),
                'processed_at' => now(),
            ]);
        }

        return $event;
    }

    private function resolveOrCreateLead(array $message, Collection $contacts, ExternalLeadEvent $event, string $messageId): Lead
    {
        $referral = $message['referral'] ?? [];
        $waId = (string) ($message['from'] ?? '');
        $whatsappNumber = PhoneNumber::normalizeWhatsapp($waId, config('spmm.whatsapp.default_country_code', '62'));

        $contactName = $contacts
            ->first(fn (array $contact): bool => (string) ($contact['wa_id'] ?? '') === $waId)['profile']['name'] ?? null;

        $sourceDetail = collect([
            'Click to WhatsApp Ad',
            'ad: '.($referral['source_id'] ?? '-'),
            'headline: '.($referral['headline'] ?? '-'),
        ])->implode(' | ');

        $referralSummary = Arr::only($referral, ['source_id', 'source_type', 'source_url', 'headline', 'body', 'media_type', 'ctwa_clid']);

        $event->update([
            'normalized_payload_json' => [
                'whatsapp_number' => $whatsappNumber,
                'contact_name' => $contactName,
                'referral' => $referralSummary,
            ],
        ]);

        $existingLead = Lead::query()
            ->where('whatsapp_number', $whatsappNumber)
            ->latest()
            ->first();

        if ($existingLead) {
            LeadActivity::create([
                'lead_id' => $existingLead->id,
                'activity_type' => ActivityType::Whatsapp,
                'note' => "Klik iklan WhatsApp (CTWA) lagi.\n".$sourceDetail
                    .(filled($referralSummary['ctwa_clid'] ?? null) ? "\nctwa_clid: ".$referralSummary['ctwa_clid'] : ''),
            ]);

            return $existingLead;
        }

        $campus = $this->resolveDefaultCampus();
        $studyProgram = $this->resolveDefaultStudyProgram($campus);
        $classTrack = $this->resolveDefaultClassTrack($campus);

        // Raw lead only — no invoice/fee scheme/WA auto-send. Staff PMB follow up manually,
        // same as a lead added by hand via "Tambah Pendaftar" in the admin panel.
        $lead = Lead::create([
            'campus_id' => $campus->id,
            'study_program_id' => $studyProgram->id,
            'class_track_id' => $classTrack->id,
            'full_name' => filled($contactName) ? $contactName : 'Lead WhatsApp '.$whatsappNumber,
            'whatsapp_number' => $whatsappNumber,
            'email' => 'lead-'.$messageId.'@meta-ctwa.local',
            'source_channel' => 'meta_ctwa',
            'source_detail' => $sourceDetail,
            'lead_status' => LeadStatus::InPool,
            'payment_status' => PaymentStatus::Pending,
            'enrollment_status' => EnrollmentStatus::CalonMahasiswa,
        ]);

        LeadActivity::create([
            'lead_id' => $lead->id,
            'activity_type' => ActivityType::Whatsapp,
            'note' => "Lead masuk dari klik iklan WhatsApp (CTWA). Menunggu follow-up staff PMB.\n".$sourceDetail
                .(filled($referralSummary['ctwa_clid'] ?? null) ? "\nctwa_clid: ".$referralSummary['ctwa_clid'] : ''),
        ]);

        return $lead;
    }

    private function resolveDefaultCampus(): Campus
    {
        $configured = config('spmm.meta_leads.default_campus_id');

        if (filled($configured)) {
            return Campus::query()->findOrFail((int) $configured);
        }

        return Campus::query()->orderBy('name')->firstOrFail();
    }

    private function resolveDefaultStudyProgram(Campus $campus): StudyProgram
    {
        $configured = config('spmm.meta_leads.default_study_program_id');

        if (filled($configured)) {
            return StudyProgram::query()->where('campus_id', $campus->id)->findOrFail((int) $configured);
        }

        return StudyProgram::query()
            ->where('campus_id', $campus->id)
            ->orderBy('degree_level')
            ->orderBy('name')
            ->firstOrFail();
    }

    private function resolveDefaultClassTrack(Campus $campus): ClassTrack
    {
        $configured = config('spmm.meta_leads.default_class_track_id');

        if (filled($configured)) {
            return ClassTrack::query()->where('campus_id', $campus->id)->findOrFail((int) $configured);
        }

        return ClassTrack::query()->where('campus_id', $campus->id)->orderBy('name')->firstOrFail();
    }
}
