<?php

namespace App\Console\Commands;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Services\Whatsapp\WhatsappManager;
use Illuminate\Console\Command;

class SendInvoiceExpiryReminders extends Command
{
    protected $signature = 'spmm:invoices:send-expiry-reminders';

    protected $description = 'Send WhatsApp reminders for invoices expiring in the next two hours.';

    public function handle(WhatsappManager $whatsapp): int
    {
        $invoices = Invoice::query()
            ->where('status', InvoiceStatus::Pending)
            ->whereBetween('expires_at', [now(), now()->addHours(2)])
            ->whereDoesntHave('lead.activities', function ($query): void {
                $query->where('activity_type', 'whatsapp')
                    ->where('note', 'like', 'expiry_reminder:%');
            })
            ->with('lead')
            ->get();

        foreach ($invoices as $invoice) {
            $whatsapp->driver()->sendExpiryReminder($invoice->lead, $invoice);

            $invoice->lead->activities()->create([
                'activity_type' => 'whatsapp',
                'note' => "expiry_reminder:{$invoice->invoice_number}",
            ]);
        }

        $this->info("Sent {$invoices->count()} invoice reminder(s).");

        return self::SUCCESS;
    }
}
