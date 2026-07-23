<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'email',
        'password',
        'verification_token',
        'password_reset_token',
        'password_reset_sent_at',
        'email_verified_at',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'verification_token',
        'password_reset_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at' => 'datetime',
            'password_reset_sent_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function referralPartners(): HasMany
    {
        return $this->hasMany(ReferralPartner::class);
    }
}
