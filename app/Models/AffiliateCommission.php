<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AffiliateCommission extends Model
{
    use HasFactory;

    public const STAGE_REGISTRATION = 'REGISTRATION';
    public const STAGE_HERREGISTRATION = 'HERREGISTRATION';
    public const STAGE_SEMESTER_1_PAID = 'SEMESTER_1_PAID';

    public const LEVEL_DIRECT = 'DIRECT';
    public const LEVEL_UPLINE_1 = 'UPLINE_1';
    public const LEVEL_UPLINE_2 = 'UPLINE_2';
    public const LEVEL_UPLINE_3 = 'UPLINE_3';

    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_PAID = 'PAID';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'referral_partner_id',
        'lead_id',
        'referral_conversion_id',
        'student_payment_id',
        'stage',
        'commission_level',
        'amount',
        'status',
        'source',
        'reference',
        'approved_at',
        'paid_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'metadata' => 'array',
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

    public function referralConversion(): BelongsTo
    {
        return $this->belongsTo(ReferralConversion::class);
    }

    public function studentPayment(): BelongsTo
    {
        return $this->belongsTo(StudentPayment::class);
    }
}
