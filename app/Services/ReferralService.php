<?php

namespace App\Services;

use App\Models\Lead;
use App\Models\ReferralConversion;
use App\Models\ReferralPartner;

class ReferralService
{
    public function __construct(private readonly AffiliateCommissionEngine $commissionEngine) {}

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
                'registration_commission_amount' => 0,
                'registration_commission_status' => 'pending',
                'herregistration_commission_amount' => 400000,
                'herregistration_commission_status' => 'pending',
                'semester1_commission_amount' => 400000,
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
        $this->commissionEngine->syncForLead($lead);
    }
}
