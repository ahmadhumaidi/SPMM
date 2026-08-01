<?php

namespace App\Observers;

use App\Models\StudentPayment;
use App\Services\AffiliateCommissionEngine;

class StudentPaymentCommissionObserver
{
    public function saved(StudentPayment $studentPayment): void
    {
        if ($studentPayment->payment_type === 'manual') {
            return;
        }

        if (! in_array((string) $studentPayment->status, ['paid', 'waived'], true)) {
            return;
        }

        $lead = $studentPayment->lead;

        if (! $lead?->referral_partner_id) {
            return;
        }

        app(AffiliateCommissionEngine::class)->syncForLead($lead);
    }
}
