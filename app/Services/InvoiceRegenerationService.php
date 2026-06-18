<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\FeeScheme;
use App\Models\Invoice;
use App\Models\Lead;
use App\Services\Payment\InvoiceService;
use App\Services\Whatsapp\WhatsappManager;
use DomainException;

class InvoiceRegenerationService
{
    public function __construct(
        private readonly InvoiceService $invoices,
        private readonly WhatsappManager $whatsapp,
        private readonly AuditLogger $audit,
    ) {
    }

    public function regenerate(Lead $lead): Invoice
    {
        $latestInvoice = $lead->latestInvoice()->first();

        if ($latestInvoice === null || ! in_array($latestInvoice->status, [InvoiceStatus::Expired, InvoiceStatus::Cancelled], true)) {
            throw new DomainException('Invoice can only be regenerated after the latest invoice is expired or cancelled.');
        }

        $feeScheme = FeeScheme::query()
            ->where('campus_id', $lead->campus_id)
            ->where(function ($query) use ($lead): void {
                $query->whereNull('study_program_id')->orWhere('study_program_id', $lead->study_program_id);
            })
            ->where(function ($query) use ($lead): void {
                $query->whereNull('class_track_id')->orWhere('class_track_id', $lead->class_track_id);
            })
            ->where('is_active', true)
            ->orderByRaw('study_program_id is null')
            ->orderByRaw('class_track_id is null')
            ->firstOrFail();

        $invoice = $this->invoices->createForLead($lead, $feeScheme, $latestInvoice);
        $this->whatsapp->driver()->sendInvoiceMessage($lead, $invoice);
        $this->audit->record('invoice_regenerated', $invoice, [
            'lead_id' => $lead->id,
            'previous_invoice_id' => $latestInvoice->id,
        ]);

        return $invoice;
    }
}
