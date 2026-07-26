<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'fee_scheme_id',
        'invoice_id',
        'month',
        'payment_label',
        'registration_fee',
        'development_fee',
        'tuition_fee',
        'ukt',
        'amount',
        'due_date',
        'status',
        'payment_method',
        'proof_path',
        'submitted_at',
        'payment_type',
        'verification_status',
        'verified_by_user_id',
        'verified_at',
        'verification_notes',
        'paid_at',
        'notes',
        'source_row_json',
        'receipt_pdf_path',
        'receipt_archived_at',
    ];

    protected function casts(): array
    {
        return [
            'month' => 'integer',
            'registration_fee' => 'integer',
            'development_fee' => 'integer',
            'tuition_fee' => 'integer',
            'ukt' => 'integer',
            'amount' => 'integer',
            'due_date' => 'date',
            'submitted_at' => 'datetime',
            'verified_at' => 'datetime',
            'paid_at' => 'datetime',
            'source_row_json' => 'array',
            'receipt_archived_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function feeScheme(): BelongsTo
    {
        return $this->belongsTo(FeeScheme::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }
}
