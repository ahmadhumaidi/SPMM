<?php

namespace App\Services\Payment;

use App\Enums\ActivityType;
use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\FeeScheme;
use App\Models\Invoice;
use App\Models\Lead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class InvoiceService
{
    public function __construct(private readonly PaymentGatewayManager $gateways)
    {
    }

    public function createForLead(Lead $lead, FeeScheme $feeScheme, ?Invoice $regeneratedFrom = null): Invoice
    {
        return DB::transaction(function () use ($lead, $feeScheme, $regeneratedFrom): Invoice {
            $activePendingInvoice = $lead->invoices()
                ->where('status', InvoiceStatus::Pending->value)
                ->where('expires_at', '>', now())
                ->lockForUpdate()
                ->first();

            if ($activePendingInvoice !== null && $regeneratedFrom === null) {
                return $activePendingInvoice;
            }

            $invoiceNumber = 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(8));

            $invoiceAmount = max((int) ($feeScheme->registration_fee ?? 0), 0);

            $invoice = $lead->invoices()->create([
                'invoice_number' => $invoiceNumber,
                'payment_gateway' => config('spmm.payment.provider'),
                'amount' => $invoiceAmount,
                'status' => InvoiceStatus::Pending,
                'expires_at' => now()->addHours(config('spmm.payment.invoice_expiry_hours')),
                'regenerated_from_invoice_id' => $regeneratedFrom?->id,
            ]);

            $result = $this->gateways->driver($invoice->payment_gateway)
                ->createInvoice($lead, [
                    'invoice_number' => $invoice->invoice_number,
                    'amount' => $invoice->amount,
                ]);

            $invoice->update([
                'gateway_reference' => $result->gatewayReference,
                'payment_method' => $result->paymentMethod,
                'payment_url' => $result->paymentUrl,
                'qr_string' => $result->qrString,
                'va_number' => $result->vaNumber,
            ]);

            $lead->update(['payment_status' => PaymentStatus::Pending]);

            if ($regeneratedFrom !== null) {
                $lead->activities()->create([
                    'activity_type' => ActivityType::InvoiceRegenerated,
                    'note' => "Invoice {$invoice->invoice_number} generated from {$regeneratedFrom->invoice_number}.",
                ]);
            }

            return $invoice->refresh();
        });
    }
}
