<?php

namespace App\Services;

use App\Models\AffiliateCommission;
use App\Models\AffiliateNetwork;
use App\Models\Lead;
use App\Models\ReferralConversion;
use App\Models\ReferralPartner;
use App\Models\StudentPayment;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class AffiliateCommissionEngine
{
    private const MAX_COMMISSION_PER_STUDENT = 1100000;
    private const PAID_STATUSES = ['paid', 'waived'];

    /**
     * @var array<string, array<string, int>>
     */
    private const DISTRIBUTIONS = [
        AffiliateCommission::STAGE_REGISTRATION => [
            AffiliateCommission::LEVEL_DIRECT => 100000,
        ],
        AffiliateCommission::STAGE_HERREGISTRATION => [
            AffiliateCommission::LEVEL_DIRECT => 400000,
            AffiliateCommission::LEVEL_UPLINE_1 => 45000,
            AffiliateCommission::LEVEL_UPLINE_2 => 32500,
            AffiliateCommission::LEVEL_UPLINE_3 => 22500,
        ],
        AffiliateCommission::STAGE_SEMESTER_1_PAID => [
            AffiliateCommission::LEVEL_DIRECT => 400000,
            AffiliateCommission::LEVEL_UPLINE_1 => 45000,
            AffiliateCommission::LEVEL_UPLINE_2 => 32500,
            AffiliateCommission::LEVEL_UPLINE_3 => 22500,
        ],
    ];

    public function syncForLead(Lead $lead): void
    {
        $lead->loadMissing(['referralPartner', 'referralConversion']);

        $directPartner = $lead->referralPartner;

        if (! $directPartner) {
            return;
        }

        $conversion = $lead->referralConversion ?: ReferralConversion::query()->firstOrCreate(
            ['lead_id' => $lead->id],
            [
                'referral_partner_id' => $directPartner->id,
                'registered_at' => $lead->created_at ?? now(),
                'commission_amount' => 0,
                'commission_status' => 'pending',
                'registration_commission_amount' => 0,
                'registration_commission_status' => 'pending',
                'herregistration_commission_amount' => 400000,
                'herregistration_commission_status' => 'pending',
                'semester1_commission_amount' => 400000,
                'semester1_commission_status' => 'pending',
            ],
        );

        if ((int) $conversion->referral_partner_id !== (int) $directPartner->id) {
            $conversion->referral_partner_id = $directPartner->id;
            $conversion->save();
        }

        $payments = $this->billablePayments($lead);

        if ($registrationPayment = $this->registrationPayment($payments)) {
            $this->createStageCommissions(
                $lead,
                $conversion,
                $directPartner,
                AffiliateCommission::STAGE_REGISTRATION,
                $registrationPayment,
            );
        }

        if ($herregistrationPayment = $this->herregistrationPayment($payments)) {
            $this->createStageCommissions(
                $lead,
                $conversion,
                $directPartner,
                AffiliateCommission::STAGE_HERREGISTRATION,
                $herregistrationPayment,
            );
        }

        if ($this->semesterOnePaid($payments)) {
            $lastSemesterPayment = $payments
                ->filter(fn (StudentPayment $payment): bool => $this->isSemesterOnePayment($payment))
                ->sortByDesc(fn (StudentPayment $payment): int => optional($payment->paid_at)->getTimestamp() ?? 0)
                ->first();

            $this->createStageCommissions(
                $lead,
                $conversion,
                $directPartner,
                AffiliateCommission::STAGE_SEMESTER_1_PAID,
                $lastSemesterPayment,
            );
        }

        $this->syncDirectConversionSummary($conversion->fresh());
    }

    /**
     * @return Collection<int, StudentPayment>
     */
    private function billablePayments(Lead $lead): Collection
    {
        return $lead->studentPayments()
            ->where(fn ($query) => $query->whereNull('payment_type')->orWhere('payment_type', '!=', 'manual'))
            ->get();
    }

    private function registrationPayment(Collection $payments): ?StudentPayment
    {
        return $payments
            ->where('month', 0)
            ->first(fn (StudentPayment $payment): bool => $this->isPaid($payment) && (int) $payment->amount >= 200000);
    }

    private function herregistrationPayment(Collection $payments): ?StudentPayment
    {
        return $payments
            ->where('month', 1)
            ->first(fn (StudentPayment $payment): bool => $this->isPaid($payment) && (int) $payment->amount >= 2500000);
    }

    private function semesterOnePaid(Collection $payments): bool
    {
        $semesterOnePayments = $payments
            ->filter(fn (StudentPayment $payment): bool => $this->isSemesterOnePayment($payment));

        return $semesterOnePayments->isNotEmpty()
            && $semesterOnePayments->every(fn (StudentPayment $payment): bool => $this->isPaid($payment));
    }

    private function isSemesterOnePayment(StudentPayment $payment): bool
    {
        return (int) $payment->month >= 1
            && (int) $payment->month <= 6
            && (int) $payment->amount > 0;
    }

    private function isPaid(StudentPayment $payment): bool
    {
        return in_array((string) $payment->status, self::PAID_STATUSES, true);
    }

    private function createStageCommissions(
        Lead $lead,
        ReferralConversion $conversion,
        ReferralPartner $directPartner,
        string $stage,
        ?StudentPayment $sourcePayment,
    ): void {
        $recipients = $this->stageRecipients($directPartner, $stage);

        foreach (self::DISTRIBUTIONS[$stage] as $level => $amount) {
            $partner = $recipients[$level] ?? null;

            if (! $partner) {
                continue;
            }

            $this->createCommissionIfAllowed($lead, $conversion, $partner, $stage, $level, $amount, $sourcePayment);
        }
    }

    /**
     * @return array<string, ReferralPartner>
     */
    private function stageRecipients(ReferralPartner $directPartner, string $stage): array
    {
        $recipients = [AffiliateCommission::LEVEL_DIRECT => $directPartner];

        if ($stage === AffiliateCommission::STAGE_REGISTRATION) {
            return $recipients;
        }

        foreach ($this->uplineChain($directPartner) as $index => $partner) {
            $recipients['UPLINE_'.($index + 1)] = $partner;
        }

        return $recipients;
    }

    /**
     * @return array<int, ReferralPartner>
     */
    private function uplineChain(ReferralPartner $directPartner): array
    {
        $uplines = [];
        $visited = [$directPartner->id => true];
        $currentPartnerId = $directPartner->id;

        for ($depth = 1; $depth <= 3; $depth++) {
            $network = AffiliateNetwork::query()
                ->with('upline')
                ->where('downline_referral_partner_id', $currentPartnerId)
                ->where('status', 'active')
                ->whereNotNull('upline_referral_partner_id')
                ->orderBy('level')
                ->orderByDesc('id')
                ->first();

            $upline = $network?->upline;

            if (! $upline || isset($visited[$upline->id])) {
                break;
            }

            $uplines[] = $upline;
            $visited[$upline->id] = true;
            $currentPartnerId = $upline->id;
        }

        return $uplines;
    }

    private function createCommissionIfAllowed(
        Lead $lead,
        ReferralConversion $conversion,
        ReferralPartner $partner,
        string $stage,
        string $level,
        int $amount,
        ?StudentPayment $sourcePayment,
    ): void {
        $exists = AffiliateCommission::query()
            ->where('lead_id', $lead->id)
            ->where('stage', $stage)
            ->where('commission_level', $level)
            ->exists();

        if ($exists) {
            return;
        }

        $currentTotal = AffiliateCommission::query()
            ->where('lead_id', $lead->id)
            ->where('status', '!=', AffiliateCommission::STATUS_CANCELLED)
            ->sum('amount');

        $allowedAmount = min($amount, max(0, self::MAX_COMMISSION_PER_STUDENT - (int) $currentTotal));

        if ($allowedAmount <= 0) {
            return;
        }

        AffiliateCommission::query()->create([
            'referral_partner_id' => $partner->id,
            'lead_id' => $lead->id,
            'referral_conversion_id' => $conversion->id,
            'student_payment_id' => $sourcePayment?->id,
            'stage' => $stage,
            'commission_level' => $level,
            'amount' => $allowedAmount,
            'status' => AffiliateCommission::STATUS_APPROVED,
            'source' => 'PAYMENT',
            'reference' => implode('-', ['AFFCOM', $lead->id, $stage, $level]),
            'approved_at' => $sourcePayment?->paid_at ?? now(),
            'metadata' => [
                'configured_amount' => $amount,
                'capped' => $allowedAmount !== $amount,
                'source_payment_status' => $sourcePayment?->status,
                'source_payment_amount' => $sourcePayment?->amount,
            ],
        ]);
    }

    private function syncDirectConversionSummary(?ReferralConversion $conversion): void
    {
        if (! $conversion) {
            return;
        }

        $directCommissions = AffiliateCommission::query()
            ->where('lead_id', $conversion->lead_id)
            ->where('commission_level', AffiliateCommission::LEVEL_DIRECT)
            ->where('status', '!=', AffiliateCommission::STATUS_CANCELLED)
            ->get()
            ->keyBy('stage');

        $registration = $directCommissions->get(AffiliateCommission::STAGE_REGISTRATION);
        $herregistration = $directCommissions->get(AffiliateCommission::STAGE_HERREGISTRATION);
        $semesterOne = $directCommissions->get(AffiliateCommission::STAGE_SEMESTER_1_PAID);

        $payload = [
            'registration_commission_amount' => $registration?->amount ?? 0,
            'registration_commission_status' => $this->conversionStatus($registration, $conversion->registration_commission_status),
            'registration_paid_at' => $registration ? ($conversion->registration_paid_at ?? $registration->approved_at ?? now()) : null,
            'herregistration_commission_amount' => $herregistration?->amount ?? 400000,
            'herregistration_commission_status' => $this->conversionStatus($herregistration, $conversion->herregistration_commission_status),
            'herregistration_paid_at' => $herregistration ? ($conversion->herregistration_paid_at ?? $herregistration->approved_at ?? now()) : null,
            'semester1_commission_amount' => $semesterOne?->amount ?? 400000,
            'semester1_commission_status' => $this->conversionStatus($semesterOne, $conversion->semester1_commission_status),
            'semester1_paid_at' => $semesterOne ? ($conversion->semester1_paid_at ?? $semesterOne->approved_at ?? now()) : null,
        ];

        $payload['paid_at'] = $payload['herregistration_paid_at'];

        $payload['commission_amount'] = collect([
            $registration?->amount ?? 0,
            $herregistration?->amount ?? 0,
            $semesterOne?->amount ?? 0,
        ])->sum();

        $statuses = collect([
            $payload['registration_commission_status'],
            $payload['herregistration_commission_status'],
            $payload['semester1_commission_status'],
        ]);

        $payload['commission_status'] = match (true) {
            $statuses->contains('approved') => 'approved',
            $statuses->contains('paid') => 'paid',
            default => 'pending',
        };

        $conversion->update($payload);
    }

    private function conversionStatus(?AffiliateCommission $commission, ?string $currentStatus): string
    {
        if (! $commission) {
            return $currentStatus === 'paid' ? 'paid' : 'pending';
        }

        return $currentStatus === 'paid' || $commission->status === AffiliateCommission::STATUS_PAID
            ? 'paid'
            : 'approved';
    }
}
