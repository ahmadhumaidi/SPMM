<?php

namespace App\Services;

use App\Models\StudentPayment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class StudentPaymentReceiptMailer
{
    public function __construct(private readonly StudentPaymentReceiptArchiver $archiver)
    {
    }

    public function send(StudentPayment $payment): void
    {
        if (! in_array($payment->status, ['paid', 'waived'], true)) {
            return;
        }

        $lead = $payment->lead()->with(['campus', 'studyProgram', 'classTrack', 'studentBiodata', 'studentNumber', 'latestInvoice'])->first();

        if (! $lead || blank($lead->email)) {
            return;
        }

        $receiptNumber = 'KWT-'.now()->format('Ymd').'-'.str_pad((string) $lead->id, 5, '0', STR_PAD_LEFT).'-'.$payment->id;
        $paymentLabel = $payment->payment_label ?: ((int) $payment->month === 0 ? 'Formulir Pendaftaran' : 'Bulan '.$payment->month);

        try {
            $pdf = $this->archiver->contentsFor($payment);
        } catch (Throwable $exception) {
            Log::warning('Student payment receipt PDF failed to render.', [
                'lead_id' => $lead->id,
                'student_payment_id' => $payment->id,
                'message' => $exception->getMessage(),
            ]);

            return;
        }

        $subject = 'Kwitansi '.$paymentLabel.' - '.$lead->full_name;
        $body = "Halo {$lead->full_name},\n\n".
            "Terima kasih, pembayaran kamu untuk {$paymentLabel} sudah kami terima dan verifikasi.\n".
            "Kwitansi resmi terlampir pada email ini.\n\n".
            "Nomor kwitansi: {$receiptNumber}\n".
            "Nominal: Rp ".number_format((int) $payment->amount, 0, ',', '.')."\n\n".
            "Salam,\nKampus Media";

        try {
            Mail::raw($body, function ($message) use ($lead, $subject, $pdf, $receiptNumber): void {
                $message->to($lead->email, $lead->full_name)
                    ->subject($subject)
                    ->attachData($pdf, 'kwitansi-'.$receiptNumber.'.pdf', ['mime' => 'application/pdf']);
            });
        } catch (Throwable $exception) {
            Log::warning('Student payment receipt email failed to send.', [
                'lead_id' => $lead->id,
                'student_payment_id' => $payment->id,
                'email' => $lead->email,
                'message' => $exception->getMessage(),
            ]);
        }

        if (app()->environment('local')) {
            Storage::disk('local')->put("local-emails/lead-{$lead->id}-kwitansi-{$payment->id}.txt", $body);
        }
    }
}
