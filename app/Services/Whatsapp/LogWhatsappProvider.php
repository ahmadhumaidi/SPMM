<?php

namespace App\Services\Whatsapp;

use App\Models\Invoice;
use App\Models\Lead;
use App\Models\WhatsappMessage;
use App\Services\Whatsapp\Contracts\WhatsappProvider;

class LogWhatsappProvider implements WhatsappProvider
{
    public function sendInvoiceMessage(Lead $lead, Invoice $invoice): WhatsappMessage
    {
        return $this->record($lead, $invoice, 'invoice_created', "Invoice {$invoice->invoice_number}: {$invoice->payment_url}");
    }

    public function sendExpiryReminder(Lead $lead, Invoice $invoice): WhatsappMessage
    {
        return $this->record($lead, $invoice, 'invoice_expiry_reminder', "Reminder invoice {$invoice->invoice_number} akan kedaluwarsa.");
    }

    public function sendPaymentSuccess(Lead $lead, Invoice $invoice): WhatsappMessage
    {
        return $this->record($lead, $invoice, 'payment_success', "Pembayaran invoice {$invoice->invoice_number} berhasil.");
    }

    public function sendPemberkasanLink(Lead $lead): WhatsappMessage
    {
        $url = url("/pemberkasan/{$lead->pemberkasan_token}");

        return $this->record($lead, null, 'document_completion_required', "Silakan lengkapi pemberkasan: {$url}");
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
