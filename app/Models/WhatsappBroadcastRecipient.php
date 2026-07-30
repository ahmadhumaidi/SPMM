<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WhatsappBroadcastRecipient extends Model
{
    protected $fillable = [
        'whatsapp_broadcast_id',
        'lead_id',
        'recipient_number',
        'recipient_name',
        'var_1',
        'var_2',
        'var_3',
        'status',
        'provider_reference',
        'attempts',
        'sent_at',
        'failed_reason',
    ];

    protected function casts(): array
    {
        return [
            'attempts' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function broadcast(): BelongsTo
    {
        return $this->belongsTo(WhatsappBroadcast::class, 'whatsapp_broadcast_id');
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }
}
