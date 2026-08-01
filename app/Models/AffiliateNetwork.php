<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateNetwork extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'upline_referral_partner_id',
        'downline_referral_partner_id',
        'level',
        'position',
        'status',
        'notes',
        'created_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'level' => 'integer',
        ];
    }

    public function upline(): BelongsTo
    {
        return $this->belongsTo(ReferralPartner::class, 'upline_referral_partner_id');
    }

    public function downline(): BelongsTo
    {
        return $this->belongsTo(ReferralPartner::class, 'downline_referral_partner_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}