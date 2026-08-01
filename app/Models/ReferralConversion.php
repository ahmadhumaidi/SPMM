<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralConversion extends Model
{
    use HasFactory;

    protected $fillable = [
        'referral_partner_id',
        'lead_id',
        'registered_at',
        'paid_at',
        'active_at',
        'commission_amount',
        'commission_status',
        'registration_commission_amount',
        'registration_commission_status',
        'registration_paid_at',
        'registration_payout_proof_path',
        'registration_payout_notes',
        'herregistration_commission_amount',
        'herregistration_commission_status',
        'herregistration_paid_at',
        'herregistration_payout_proof_path',
        'herregistration_payout_notes',
        'semester1_commission_amount',
        'semester1_commission_status',
        'semester1_paid_at',
        'semester1_payout_proof_path',
        'semester1_payout_notes',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'paid_at' => 'datetime',
            'active_at' => 'datetime',
            'commission_amount' => 'integer',
            'registration_commission_amount' => 'integer',
            'registration_paid_at' => 'datetime',
            'herregistration_commission_amount' => 'integer',
            'herregistration_paid_at' => 'datetime',
            'semester1_commission_amount' => 'integer',
            'semester1_paid_at' => 'datetime',
        ];
    }

    public function referralPartner(): BelongsTo
    {
        return $this->belongsTo(ReferralPartner::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function affiliateCommissions(): HasMany
    {
        return $this->hasMany(AffiliateCommission::class);
    }
}
