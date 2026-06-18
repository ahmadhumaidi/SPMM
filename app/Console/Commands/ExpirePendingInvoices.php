<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Invoice;
use App\Services\AuditLogger;
use Illuminate\Console\Command;

class ExpirePendingInvoices extends Command
{
    protected $signature = 'spmm:invoices:expire-pending';

    protected $description = 'Mark pending invoices as expired after their expiry time.';

    public function handle(AuditLogger $audit): int
    {
        $expired = Invoice::query()
            ->where('status', InvoiceStatus::Pending)
            ->where('expires_at', '<=', now())
            ->with('lead')
            ->get();

        foreach ($expired as $invoice) {
            $invoice->update(['status' => InvoiceStatus::Expired]);
            $audit->record('invoice_expired', $invoice, ['lead_id' => $invoice->lead_id]);

            if ($invoice->lead->payment_status !== PaymentStatus::Paid) {
                $invoice->lead->update(['payment_status' => PaymentStatus::Expired]);
            }
        }

        $this->info("Expired {$expired->count()} invoice(s).");

        return self::SUCCESS;
    }
}
