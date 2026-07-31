<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class WhatsappBroadcastRecipient extends Model
{
    protected $fillable = [
        'whatsapp_broadcast_id', 'lead_id', 'recipient_number', 'recipient_name', 'status',
        'provider_reference', 'attempts', 'sent_at', 'delivered_at', 'read_at', 'failed_reason',
    ];

    protected function casts(): array
    {
        return ['sent_at' => 'datetime', 'delivered_at' => 'datetime', 'read_at' => 'datetime'];
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(WhatsappBroadcast::class, 'whatsapp_broadcast_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function message(): HasOne
    {
        return $this->hasOne(WhatsappMessage::class);
    }
}
