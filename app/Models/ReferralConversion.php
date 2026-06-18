<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'paid_at' => 'datetime',
            'active_at' => 'datetime',
            'commission_amount' => 'integer',
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
}
