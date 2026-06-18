<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Models\FeeScheme;
use App\Models\Lead;
use App\Models\StudentBiodata;

class StudentBiodataProvisioner
{
    public function createForPaidRegistration(Lead $lead): ?StudentBiodata
    {
        $lead->loadMissing(['classTrack', 'studentBiodata', 'studentNumber']);

        if ($lead->payment_status !== PaymentStatus::Paid) {
            return null;
        }

        $feeScheme = $this->feeSchemeFor($lead);

        return $lead->studentBiodata()->updateOrCreate(
            ['lead_id' => $lead->id],
            [
                'student_type' => 'new',
                'campus_id' => $lead->campus_id,
                'study_program_id' => $lead->study_program_id,
                'class_track_id' => $lead->class_track_id,
                'information_source' => $this->informationSourceFor($lead),
                'affiliator_code' => $lead->referral_code,
                'name' => $lead->full_name,
                'selection_number' => $lead->studentBiodata?->selection_number ?? $this->selectionNumberFor($lead),
                'student_number' => $lead->studentNumber?->nim,
                'group' => $lead->classTrack?->name,
                'financial_status' => 'Paid',
                'email' => $lead->email,
                'whatsapp_number' => $lead->whatsapp_number,
                'development_contribution' => $feeScheme?->building_fee ?? 0,
                'semester_education_contribution' => $feeScheme?->monthly_tuition_fee ?: ($feeScheme?->ukt_total ?? 0),
                'almamater_jacket_fee' => 0,
                'form_fee' => $feeScheme?->registration_fee ?? 0,
            ],
        );
    }

    public function selectionNumberFor(Lead $lead): string
    {
        return 'SEL-'.($lead->created_at?->format('Ymd') ?? now()->format('Ymd')).'-'.str_pad((string) $lead->id, 5, '0', STR_PAD_LEFT);
    }

    protected function feeSchemeFor(Lead $lead): ?FeeScheme
    {
        return FeeScheme::query()
            ->where('campus_id', $lead->campus_id)
            ->where(function ($query) use ($lead): void {
                $query->whereNull('study_program_id')
                    ->orWhere('study_program_id', $lead->study_program_id);
            })
            ->where(function ($query) use ($lead): void {
                $query->whereNull('class_track_id')
                    ->orWhere('class_track_id', $lead->class_track_id);
            })
            ->where('is_active', true)
            ->orderByRaw('study_program_id is null')
            ->orderByRaw('class_track_id is null')
            ->first();
    }

    protected function informationSourceFor(Lead $lead): ?string
    {
        return match ($lead->source_channel) {
            'tiktok' => 'Tiktok',
            'instagram' => 'Instagram',
            'facebook' => 'Facebook',
            'youtube' => 'Youtube',
            'ads', 'iklan', 'meta_ads', 'google_ads' => 'Iklan',
            'referral', 'affiliate', 'affiliator' => 'Affiliator',
            default => $lead->source_detail ?: $lead->source_channel,
        };
    }
}
