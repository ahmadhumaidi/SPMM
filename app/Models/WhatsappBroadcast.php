<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappBroadcast extends Model
{
    protected $fillable = [
        'created_by_user_id',
        'name',
        'message_body',
        'status',
        'recipient_count',
        'sent_count',
        'failed_count',
        'queued_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'recipient_count' => 'integer',
            'sent_count' => 'integer',
            'failed_count' => 'integer',
            'queued_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(WhatsappBroadcastRecipient::class);
    }

    public function renderMessageForRecipient(WhatsappBroadcastRecipient $recipient): string
    {
        return str_replace(
            ['{{nama}}', '{{1}}', '{{2}}', '{{3}}'],
            [$recipient->recipient_name ?? '', $recipient->var_1 ?? '', $recipient->var_2 ?? '', $recipient->var_3 ?? ''],
            $this->message_body ?? '',
        );
    }
}
