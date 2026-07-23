<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\ReferralConversion;
use App\Models\ReferralPartner;

class ReferralService
{
    public function partnerForCode(?string $code): ?ReferralPartner
    {
        if (blank($code)) {
            return null;
        }

        return ReferralPartner::query()
            ->where('referral_code', trim($code))
            ->where('status', 'active')
            ->first();
    }

    public function recordRegistration(Lead $lead, ReferralPartner $partner): void
    {
        $lead->update([
            'referral_partner_id' => $partner->id,
            'referral_code' => $partner->referral_code,
            'source_channel' => 'affiliator',
            'source_detail' => 'Affiliator: '.$partner->name.' ('.$partner->referral_code.')',
        ]);

        ReferralConversion::query()->updateOrCreate(
            ['lead_id' => $lead->id],
            [
                'referral_partner_id' => $partner->id,
                'registered_at' => $lead->created_at ?? now(),
                'commission_amount' => 0,
                'commission_status' => 'pending',
                'herregistration_commission_amount' => 500000,
                'herregistration_commission_status' => 'pending',
                'semester1_commission_amount' => 500000,
                'semester1_commission_status' => 'pending',
            ],
        );
    }

    public function markPaid(Lead $lead): void
    {
        $this->syncMilestones($lead);
    }

    public function syncMilestones(Lead $lead): void
    {
        $conversion = $lead->referralConversion;

        if (! $conversion) {
            return;
        }

        $payments = $lead->studentPayments()
            ->where(fn ($query) => $query->whereNull('payment_type')->orWhere('payment_type', '!=', 'manual'))
            ->where('month', '>', 0)
            ->get();

        $paidStatuses = ['paid', 'waived'];
        $herregistrationPayment = $payments
            ->where('month', 1)
            ->first(fn ($payment): bool => in_array($payment->status, $paidStatuses, true));

        $semesterOnePayments = $payments
            ->filter(fn ($payment): bool => (int) $payment->month >= 1 && (int) $payment->month <= 6)
            ->filter(fn ($payment): bool => (int) $payment->amount > 0);

        $semesterOnePaid = $semesterOnePayments->isNotEmpty()
            && $semesterOnePayments->every(fn ($payment): bool => in_array($payment->status, $paidStatuses, true));

        $payload = [
            'herregistration_commission_amount' => $conversion->herregistration_commission_amount ?: 500000,
            'semester1_commission_amount' => $conversion->semester1_commission_amount ?: 500000,
        ];

        if ($herregistrationPayment) {
            $payload['paid_at'] = $conversion->paid_at ?? $herregistrationPayment->paid_at ?? now();
            $payload['herregistration_paid_at'] = $conversion->herregistration_paid_at ?? $herregistrationPayment->paid_at ?? now();
            $payload['herregistration_commission_status'] = $conversion->herregistration_commission_status === 'paid' ? 'paid' : 'approved';
        } elseif ($conversion->herregistration_commission_status !== 'paid') {
            $payload['paid_at'] = null;
            $payload['herregistration_paid_at'] = null;
            $payload['herregistration_commission_status'] = 'pending';
        }

        if ($semesterOnePaid) {
            $lastSemesterPayment = $semesterOnePayments->sortByDesc('paid_at')->first();
            $payload['semester1_paid_at'] = $conversion->semester1_paid_at ?? $lastSemesterPayment?->paid_at ?? now();
            $payload['semester1_commission_status'] = $conversion->semester1_commission_status === 'paid' ? 'paid' : 'approved';
        } elseif ($conversion->semester1_commission_status !== 'paid') {
            $payload['semester1_paid_at'] = null;
            $payload['semester1_commission_status'] = 'pending';
        }

        $herStatus = $payload['herregistration_commission_status'] ?? $conversion->herregistration_commission_status;
        $semesterStatus = $payload['semester1_commission_status'] ?? $conversion->semester1_commission_status;

        $payload['commission_amount'] = collect([
            in_array($herStatus, ['approved', 'paid'], true) ? ($payload['herregistration_commission_amount'] ?? 500000) : 0,
            in_array($semesterStatus, ['approved', 'paid'], true) ? ($payload['semester1_commission_amount'] ?? 500000) : 0,
        ])->sum();

        $payload['commission_status'] = match (true) {
            $herStatus === 'paid' && $semesterStatus === 'paid' => 'paid',
            in_array($herStatus, ['approved', 'paid'], true) || in_array($semesterStatus, ['approved', 'paid'], true) => 'approved',
            default => 'pending',
        };

        $conversion->update($payload);
    }
}
