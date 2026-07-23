<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReferralPartner extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'user_id',
        'student_account_id',
        'email',
        'password',
        'phone',
        'address',
        'source_information',
        'npwp',
        'bank_name',
        'bank_account_number',
        'bank_account_name',
        'identity_card_path',
        'terms_document_path',
        'terms_accepted_at',
        'tax_notice_accepted_at',
        'digital_signature_path',
        'verification_token',
        'dashboard_token',
        'password_reset_token',
        'password_reset_sent_at',
        'email_verified_at',
        'referral_code',
        'commission_amount',
        'status',
        'notes',
    ];

    protected $hidden = [
        'password',
        'verification_token',
        'dashboard_token',
        'password_reset_token',
    ];

    protected function casts(): array
    {
        return [
            'commission_amount' => 'integer',
            'email_verified_at' => 'datetime',
            'password_reset_sent_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'tax_notice_accepted_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function studentAccount(): BelongsTo
    {
        return $this->belongsTo(StudentAccount::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function conversions(): HasMany
    {
        return $this->hasMany(ReferralConversion::class);
    }
}
