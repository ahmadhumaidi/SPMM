<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Lead;
use App\Services\Payment\Contracts\PaymentGateway;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class MockPaymentGateway implements PaymentGateway
{
    public function createInvoice(Lead $lead, array $payload): InvoiceResult
    {
        $reference = 'MOCK-'.Str::upper(Str::random(16));

        return new InvoiceResult(
            gatewayReference: $reference,
            paymentUrl: url("/mock-payment/{$reference}"),
            paymentMethod: $payload['payment_method'] ?? 'mock',
        );
    }

    public function validateWebhook(Request $request): bool
    {
        return $request->header('X-Mock-Signature') === config('app.key')
            || app()->environment('local');
    }

    public function parseWebhook(Request $request): PaymentWebhookResult
    {
        return new PaymentWebhookResult(
            gatewayReference: (string) $request->input('gateway_reference'),
            eventType: (string) $request->input('event_type', 'payment.updated'),
            isPaid: (bool) $request->boolean('paid'),
            payload: $request->all(),
        );
    }

    public function getInvoiceStatus(Invoice $invoice): string
    {
        return $invoice->status->value;
    }
}
