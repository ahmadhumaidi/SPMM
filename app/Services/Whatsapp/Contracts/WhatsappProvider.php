<?php

namespace App\Services\Whatsapp\Contracts;

use App\Models\Invoice;
use App\Models\Lead;
use App\Models\WhatsappMessage;

interface WhatsappProvider
{
    public function sendInvoiceMessage(Lead $lead, Invoice $invoice): WhatsappMessage;

    public function sendExpiryReminder(Lead $lead, Invoice $invoice): WhatsappMessage;

    public function sendPaymentSuccess(Lead $lead, Invoice $invoice): WhatsappMessage;

    public function sendPemberkasanLink(Lead $lead): WhatsappMessage;
}
