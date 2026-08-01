<?php

namespace App\Services\Whatsapp;

use App\Models\Lead;
use App\Models\WhatsappBroadcast;
use App\Models\WhatsappBroadcastRecipient;
use App\Support\FilamentResourceScope;
use App\Support\PhoneNumber;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class WhatsappBroadcastService
{
    public function queue(WhatsappBroadcast $broadcast): int
    {
        if ($broadcast->status !== 'draft') {
            throw new DomainException('Hanya broadcast berstatus draft yang dapat dikirim.');
        }

        $count = DB::transaction(function () use ($broadcast): int {
            $count = $this->queueFromLeadFilter($broadcast);
            $count += $this->queueFromUploadedFile($broadcast);

            if ($count === 0) {
                throw new DomainException('Tidak ada penerima baru untuk filter/nomor yang diberikan.');
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

    private function queueFromLeadFilter(WhatsappBroadcast $broadcast): int
    {
        if (! $broadcast->include_leads) {
            return 0;
        }

        $query = FilamentResourceScope::applyManagedLeadCampusScope(Lead::query())
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
            $recipient = WhatsappBroadcastRecipient::firstOrCreate(
                ['whatsapp_broadcast_id' => $broadcast->id, 'lead_id' => $lead->id],
                ['recipient_number' => $lead->whatsapp_number, 'status' => 'queued'],
            );

            if ($recipient->wasRecentlyCreated) {
                $count++;
            }
        });

        return $count;
    }

    private function queueFromUploadedFile(WhatsappBroadcast $broadcast): int
    {
        if (blank($broadcast->recipients_file_path)) {
            return 0;
        }

        $count = 0;
        $existingNumbers = $broadcast->recipients()
            ->pluck('recipient_number')
            ->map(fn (string $number): string => $this->normalizeRecipientNumber($number))
            ->filter()
            ->flip();

        foreach ($this->parseRecipientsFile($broadcast->recipients_file_path) as $row) {
            $number = $this->normalizeRecipientNumber($row['recipient_number']);

            if ($number === '' || $existingNumbers->has($number)) {
                continue;
            }

            $recipient = WhatsappBroadcastRecipient::create([
                'whatsapp_broadcast_id' => $broadcast->id,
                'lead_id' => null,
                'recipient_number' => $number,
                'recipient_name' => $row['recipient_name'],
                'status' => 'queued',
            ]);

            if ($recipient->wasRecentlyCreated) {
                $existingNumbers->put($number, true);
                $count++;
            }
        }

        Storage::disk('local')->delete($broadcast->recipients_file_path);
        $broadcast->update(['recipients_file_path' => null]);

        return $count;
    }

    private function normalizeRecipientNumber(string $number): string
    {
        $normalized = PhoneNumber::normalizeWhatsapp($number, config('spmm.whatsapp.default_country_code'));

        return strlen($normalized) >= 9 ? $normalized : '';
    }
    /**
     * @return array<int, array{recipient_number: string, recipient_name: ?string}>
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
                'recipient_number' => $number,
                'recipient_name' => $name !== '' ? $name : null,
            ];
        }

        return $rows;
    }
}
