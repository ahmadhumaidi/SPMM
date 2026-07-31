<?php

namespace App\Services\Whatsapp;

use App\Models\Invoice;
use App\Models\Lead;
use App\Models\WhatsappMessage;
use App\Services\Whatsapp\Contracts\WhatsappProvider;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class N8nWhatsappProvider implements WhatsappProvider
{
    public function sendInvoiceMessage(Lead $lead, Invoice $invoice): WhatsappMessage
    {
        $message = "Invoice {$invoice->invoice_number} telah dibuat. Silakan lakukan pembayaran: {$invoice->payment_url}";

        return $this->dispatch($lead, $invoice, 'invoice_created', $message, 'pendaftar-baru', [
            'nama' => $lead->full_name,
            'whatsapp' => $lead->whatsapp_number,
            'kampus' => $lead->campus?->name,
            'program_studi' => $lead->studyProgram?->name,
            'program_perkuliahan' => $lead->classTrack?->name,
        ]);
    }

    public function sendPaymentSuccess(Lead $lead, Invoice $invoice): WhatsappMessage
    {
        $message = 'Pembayaran invoice '.$invoice->invoice_number.' sebesar Rp'.number_format($invoice->amount, 0, ',', '.').' berhasil dikonfirmasi.';

        return $this->dispatch($lead, $invoice, 'payment_success', $message, 'pembayaran-masuk', [
            'nama' => $lead->full_name,
            'whatsapp' => $lead->whatsapp_number,
            'jumlah' => (string) $invoice->amount,
            'keterangan' => $invoice->invoice_number,
            'kampus' => $lead->campus?->name,
        ]);
    }

    public function sendExpiryReminder(Lead $lead, Invoice $invoice): WhatsappMessage
    {
        return $this->record($lead, $invoice, 'invoice_expiry_reminder', "Reminder invoice {$invoice->invoice_number} akan kedaluwarsa.");
    }

    public function sendPemberkasanLink(Lead $lead): WhatsappMessage
    {
        $url = url("/pemberkasan/{$lead->pemberkasan_token}");

        return $this->record($lead, null, 'document_completion_required', "Silakan lengkapi pemberkasan: {$url}");
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function dispatch(Lead $lead, ?Invoice $invoice, string $templateKey, string $message, string $webhookPath, array $payload): WhatsappMessage
    {
        $status = 'failed';
        $reference = null;
        $failedReason = null;

        try {
            $response = Http::timeout(10)
                ->asJson()
                ->post(rtrim((string) config('spmm.whatsapp.n8n_webhook_base'), '/')."/{$webhookPath}", $payload);

            if ($response->successful()) {
                $status = 'sent';
                $reference = (string) $response->json('message');
            } else {
                $failedReason = "HTTP {$response->status()}: ".$response->body();
            }
        } catch (Throwable $e) {
            $failedReason = $e->getMessage();
            Log::error('n8n WhatsApp dispatch failed', ['error' => $e->getMessage(), 'lead_id' => $lead->id, 'webhook' => $webhookPath]);
        }

        return WhatsappMessage::create([
            'lead_id' => $lead->id,
            'invoice_id' => $invoice?->id,
            'recipient_number' => $lead->whatsapp_number,
            'template_key' => $templateKey,
            'message' => $message,
            'provider_reference' => $reference,
            'status' => $status,
            'sent_at' => $status === 'sent' ? now() : null,
            'failed_reason' => $failedReason,
        ]);
    }

    private function record(Lead $lead, ?Invoice $invoice, string $templateKey, string $message): WhatsappMessage
    {
        return WhatsappMessage::create([
            'lead_id' => $lead->id,
            'invoice_id' => $invoice?->id,
            'recipient_number' => $lead->whatsapp_number,
            'template_key' => $templateKey,
            'message' => $message,
            'status' => 'sent',
            'sent_at' => now(),
        ]);
    }
}
