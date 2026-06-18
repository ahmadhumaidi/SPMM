<?php

namespace App\Models;

use App\Enums\InvoiceStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'invoice_number',
        'payment_gateway',
        'gateway_reference',
        'amount',
        'payment_method',
        'payment_url',
        'qr_string',
        'va_number',
        'status',
        'expires_at',
        'paid_at',
        'regenerated_from_invoice_id',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'status' => InvoiceStatus::class,
            'expires_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function regeneratedFrom(): BelongsTo
    {
        return $this->belongsTo(Invoice::class, 'regenerated_from_invoice_id');
    }

    public function paymentEvents(): HasMany
    {
        return $this->hasMany(PaymentEvent::class);
    }

    public function isPayable(): bool
    {
        return $this->status === InvoiceStatus::Pending && $this->expires_at->isFuture();
    }
}
