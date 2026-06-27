<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadProspectEvent extends Model
{
    protected $fillable = [
        'lead_id',
        'user_id',
        'from_status',
        'to_status',
        'meta_event_name',
        'meta_payload_json',
        'meta_status',
        'meta_error_message',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'meta_payload_json' => 'array',
            'sent_at' => 'datetime',
        ];
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
