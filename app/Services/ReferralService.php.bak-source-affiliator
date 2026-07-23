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
        ]);

        ReferralConversion::query()->updateOrCreate(
            ['lead_id' => $lead->id],
            [
                'referral_partner_id' => $partner->id,
                'registered_at' => $lead->created_at ?? now(),
                'commission_amount' => $partner->commission_amount,
                'commission_status' => 'pending',
            ],
        );
    }

    public function markPaid(Lead $lead): void
    {
        $conversion = $lead->referralConversion;

        if (! $conversion) {
            return;
        }

        $conversion->update([
            'paid_at' => $conversion->paid_at ?? now(),
            'commission_status' => $conversion->commission_status === 'pending' ? 'approved' : $conversion->commission_status,
        ]);
    }
}
