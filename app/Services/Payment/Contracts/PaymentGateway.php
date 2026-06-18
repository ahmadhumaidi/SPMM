<?php

namespace App\Services\Payment\Contracts;

use App\Models\Invoice;
use App\Models\Lead;
use App\Services\Payment\InvoiceResult;
use App\Services\Payment\PaymentWebhookResult;
use Illuminate\Http\Request;

interface PaymentGateway
{
    public function createInvoice(Lead $lead, array $payload): InvoiceResult;

    public function validateWebhook(Request $request): bool;

    public function parseWebhook(Request $request): PaymentWebhookResult;

    public function getInvoiceStatus(Invoice $invoice): string;
}
