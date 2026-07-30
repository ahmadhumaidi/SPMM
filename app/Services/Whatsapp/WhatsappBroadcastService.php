<?php

namespace App\Services\Whatsapp;

use App\Models\Lead;
use App\Models\WhatsappBroadcast;
use App\Models\WhatsappBroadcastRecipient;
use DomainException;
use Illuminate\Support\Facades\DB;

class WhatsappBroadcastService
{
    public function queue(WhatsappBroadcast $broadcast): int
    {
        if ($broadcast->status !== 'draft') {
            throw new DomainException('Hanya broadcast berstatus draft yang dapat dikirim.');
        }

        $count = DB::transaction(function () use ($broadcast): int {
            $query = Lead::query()
                ->whereNotNull('whatsapp_number');

            if ($broadcast->campus_id) {
                $query->where('campus_id', $broadcast->campus_id);
            }
            if ($broadcast->lead_status) {
                $query->where('lead_status', $broadcast->lead_status);
            }

            $count = 0;
            $limit = max(1, (int) ($broadcast->max_recipients ?: 50));

            $query->select(['id', 'whatsapp_number'])->orderBy('id')->limit($limit)->get()->each(function (Lead $lead) use ($broadcast, &$count): void {
                if ($count >= max(1, (int) ($broadcast->max_recipients ?: 50))) {
                    return;
                }

                $recipient = WhatsappBroadcastRecipient::firstOrCreate(
                    ['whatsapp_broadcast_id' => $broadcast->id, 'lead_id' => $lead->id],
                    ['recipient_number' => $lead->whatsapp_number, 'status' => 'queued'],
                );

                if ($recipient->wasRecentlyCreated) {
                    $count++;
                }
            });

            if ($count === 0) {
                throw new DomainException('Tidak ada penerima baru dengan nomor WhatsApp untuk filter ini.');
            }

            $broadcast->update([
                'status' => 'queued',
                'recipient_count' => $count,
                'queued_at' => now(),
            ]);

            return $count;
        });

        return $count;
    }
}
