<?php

namespace App\Services;

use App\Models\Invoice;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class InvoicePaymentReminderMailer
{
    public function send(Invoice $invoice): void
    {
        if (! $invoice->isPayable()) {
            return;
        }

        $lead = $invoice->relationLoaded('lead')
            ? $invoice->lead
            : $invoice->lead()->with(['campus', 'studyProgram'])->first();

        if (! $lead || blank($lead->email)) {
            return;
        }

        $html = view('admin.invoice-payment-reminder', [
            'invoice' => $invoice,
            'lead' => $lead,
        ])->render();

        $subject = 'Tagihan Pendaftaran Anda - '.($lead->campus?->name ?? 'Kampus Media').' ('.$invoice->invoice_number.')';

        try {
            Mail::html($html, function ($message) use ($lead, $subject): void {
                $message->to($lead->email, $lead->full_name)
                    ->subject($subject);
            });
        } catch (Throwable $exception) {
            Log::warning('Invoice payment reminder email failed to send.', [
                'lead_id' => $lead->id,
                'invoice_id' => $invoice->id,
                'email' => $lead->email,
                'message' => $exception->getMessage(),
            ]);

            return;
        }

        if (app()->environment('local')) {
            Storage::disk('local')->put("local-emails/lead-{$lead->id}-invoice-{$invoice->id}.html", $html);
        }
    }
}
