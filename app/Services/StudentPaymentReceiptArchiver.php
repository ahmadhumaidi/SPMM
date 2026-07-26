<?php

namespace App\Services;

use App\Models\StudentPayment;
use App\Support\ReceiptPdfRenderer;
use Illuminate\Support\Facades\Storage;

class StudentPaymentReceiptArchiver
{
    public function contentsFor(StudentPayment $payment): string
    {
        if ($payment->receipt_pdf_path && Storage::disk('local')->exists($payment->receipt_pdf_path)) {
            return Storage::disk('local')->get($payment->receipt_pdf_path);
        }

        $lead = $payment->relationLoaded('lead')
            ? $payment->lead
            : $payment->lead()->with(['campus', 'studyProgram', 'classTrack', 'studentBiodata', 'studentNumber', 'latestInvoice'])->firstOrFail();

        $receiptNumber = 'KWT-'.$payment->created_at->format('Ymd').'-'.str_pad((string) $lead->id, 5, '0', STR_PAD_LEFT).'-'.$payment->id;

        $pdf = ReceiptPdfRenderer::render('admin.student-payment-receipt', [
            'lead' => $lead,
            'paidPayments' => collect([$payment]),
            'receiptNumber' => $receiptNumber,
            'totalPaid' => (int) $payment->amount,
            'receiptUrl' => route('admin.student-payments.transaction-receipt', $payment),
        ], 'kwitansi-'.$lead->id.'-'.$payment->id.'.pdf')->getContent();

        $path = 'receipts/kwitansi-'.$lead->id.'-'.$payment->id.'.pdf';
        Storage::disk('local')->put($path, $pdf);
        $payment->update(['receipt_pdf_path' => $path, 'receipt_archived_at' => now()]);

        return $pdf;
    }
}
