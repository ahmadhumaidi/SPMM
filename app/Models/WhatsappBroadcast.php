<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WhatsappBroadcast extends Model
{
    protected $fillable = [
        'campus_id', 'created_by_user_id', 'name', 'template_name', 'template_language',
        'message_body', 'interval_seconds', 'max_recipients', 'lead_status', 'status',
        'recipients_file_path', 'recipient_count', 'sent_count', 'delivered_count',
        'read_count', 'failed_count', 'queued_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'interval_seconds' => 'integer',
            'max_recipients' => 'integer',
            'queued_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function campus(): BelongsTo
    {
        return $this->belongsTo(Campus::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(WhatsappBroadcastRecipient::class);
    }

    public function nextWhatsappWebUrl(): ?string
    {
        $recipient = $this->recipients()
            ->with(['lead.latestInvoice', 'lead.studyProgram'])
            ->where('status', 'queued')
            ->orderBy('id')
            ->first();

        if (! $recipient) {
            return null;
        }

        return $this->whatsappWebUrlForRecipient($recipient);
    }

    public function whatsappWebUrlForRecipient(WhatsappBroadcastRecipient $recipient): string
    {
        $phone = preg_replace('/\D+/', '', $recipient->recipient_number);

        if (str_starts_with($phone, '0')) {
            $phone = '62'.substr($phone, 1);
        } elseif (str_starts_with($phone, '8')) {
            $phone = '62'.$phone;
        }

        $message = $this->renderMessageForRecipient($recipient);

        return 'https://wa.me/'.$phone.'?text='.rawurlencode($message);
    }

    public function renderMessageForRecipient(WhatsappBroadcastRecipient $recipient): string
    {
        $recipient->loadMissing(['lead.latestInvoice', 'lead.studyProgram']);

        $lead = $recipient->lead;
        $tagihan = $lead?->latestInvoice
            ? 'Rp'.number_format((int) $lead->latestInvoice->amount, 0, ',', '.')
            : 'Belum ada tagihan';

        $name = $lead?->full_name ?? $recipient->recipient_name ?? 'Kak';

        return strtr($this->message_body ?? '', [
            '{nama}' => $name,
            '{name}' => $name,
            '{nomor}' => $recipient->recipient_number,
            '{jurusan}' => $lead?->studyProgram?->name ?? '-',
            '{tagihan}' => $tagihan,
        ]);
    }
}
