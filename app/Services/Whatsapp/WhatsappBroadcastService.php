<?php

namespace App\Services\Whatsapp;

use App\Jobs\SendWhatsappBroadcastRecipient;
use App\Models\Lead;
use App\Models\WhatsappBroadcast;
use App\Models\WhatsappBroadcastRecipient;
use DomainException;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class WhatsappBroadcastService
{
    /**
     * Build WhatsappBroadcastRecipient rows from raw form input
     * (lead_ids, manual_numbers, recipients_file) and attach them to the broadcast.
     *
     * @param  array<string, mixed>  $rawData
     */
    public function buildRecipients(WhatsappBroadcast $broadcast, array $rawData): int
    {
        $recipients = [];

        if (! empty($rawData['lead_ids'])) {
            foreach (Lead::query()->whereIn('id', $rawData['lead_ids'])->get() as $lead) {
                if (filled($lead->whatsapp_number)) {
                    $recipients[] = [
                        'lead_id' => $lead->id,
                        'recipient_number' => $lead->whatsapp_number,
                        'recipient_name' => $lead->full_name,
                    ];
                }
            }
        }

        if (! empty($rawData['manual_numbers'])) {
            foreach (preg_split('/\r\n|\r|\n/', (string) $rawData['manual_numbers']) as $line) {
                $number = trim($line);
                if ($number !== '') {
                    $recipients[] = [
                        'lead_id' => null,
                        'recipient_number' => $number,
                        'recipient_name' => null,
                    ];
                }
            }
        }

        if (! empty($rawData['recipients_file'])) {
            foreach ($this->parseRecipientsFile((string) $rawData['recipients_file']) as $row) {
                $recipients[] = $row;
            }

            Storage::disk('local')->delete((string) $rawData['recipients_file']);
        }

        if (empty($recipients)) {
            throw new DomainException('Tidak ada penerima yang dipilih.');
        }

        foreach ($recipients as $recipient) {
            WhatsappBroadcastRecipient::create([
                'whatsapp_broadcast_id' => $broadcast->id,
                'lead_id' => $recipient['lead_id'],
                'recipient_number' => $recipient['recipient_number'],
                'recipient_name' => $recipient['recipient_name'],
                'var_1' => $recipient['var_1'] ?? null,
                'var_2' => $recipient['var_2'] ?? null,
                'var_3' => $recipient['var_3'] ?? null,
                'status' => 'queued',
            ]);
        }

        $count = count($recipients);

        $broadcast->update(['recipient_count' => $count]);

        return $count;
    }

    public function dispatch(WhatsappBroadcast $broadcast): void
    {
        if ($broadcast->status !== 'draft') {
            throw new DomainException('Broadcast ini sudah pernah dikirim.');
        }

        $recipientIds = $broadcast->recipients()->where('status', 'queued')->pluck('id');

        if ($recipientIds->isEmpty()) {
            throw new DomainException('Tidak ada penerima untuk dikirim.');
        }

        foreach ($recipientIds as $recipientId) {
            SendWhatsappBroadcastRecipient::dispatch($recipientId);
        }

        $broadcast->update([
            'status' => 'queued',
            'queued_at' => now(),
        ]);
    }

    /**
     * @return array<int, array{lead_id: null, recipient_number: string, recipient_name: ?string, var_1: ?string, var_2: ?string, var_3: ?string}>
     */
    private function parseRecipientsFile(string $relativePath): array
    {
        $fullPath = Storage::disk('local')->path($relativePath);

        if (! is_file($fullPath)) {
            return [];
        }

        $reader = IOFactory::createReaderForFile($fullPath);
        $reader->setReadDataOnly(true);
        $sheet = $reader->load($fullPath)->getActiveSheet();

        $rows = [];

        foreach ($sheet->toArray(null, true, true, false) as $index => $row) {
            $name = isset($row[0]) ? trim((string) $row[0]) : '';
            $number = isset($row[1]) ? trim((string) $row[1]) : '';
            $var1 = isset($row[2]) ? trim((string) $row[2]) : '';
            $var2 = isset($row[3]) ? trim((string) $row[3]) : '';
            $var3 = isset($row[4]) ? trim((string) $row[4]) : '';

            if ($number === '' && $name !== '') {
                $number = $name;
                $name = '';
            }

            if ($number === '') {
                continue;
            }

            if ($index === 0 && preg_match('/^(nama|name|nomor|no\.?\s*wa|whatsapp|phone|number)$/i', $number)) {
                continue;
            }

            $rows[] = [
                'lead_id' => null,
                'recipient_number' => $number,
                'recipient_name' => $name !== '' ? $name : null,
                'var_1' => $var1 !== '' ? $var1 : null,
                'var_2' => $var2 !== '' ? $var2 : null,
                'var_3' => $var3 !== '' ? $var3 : null,
            ];
        }

        return $rows;
    }
}
