<?php

namespace App\Services\Payment;

use App\Models\Invoice;
use App\Models\Lead;
use App\Services\Payment\Contracts\PaymentGateway;
use Illuminate\Http\Request;
use Midtrans\Config;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransPaymentGateway implements PaymentGateway
{
    private const ENABLED_PAYMENTS = [
        'gopay',
        'shopeepay',
        'qris',
        'bca_va',
        'bni_va',
        'bri_va',
        'permata_va',
        'other_va',
    ];

    public function __construct()
    {
        Config::$serverKey = (string) config('spmm.payment.midtrans.server_key');
        Config::$isProduction = (bool) config('spmm.payment.midtrans.is_production');
        Config::$isSanitized = true;
        Config::$is3ds = true;
    }

    public function createInvoice(Lead $lead, array $payload): InvoiceResult
    {
        $orderId = (string) $payload['invoice_number'];

        $result = Snap::createTransaction([
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $payload['amount'],
            ],
            'customer_details' => [
                'first_name' => $lead->full_name,
                'email' => $lead->email,
                'phone' => $lead->whatsapp_number,
            ],
            'enabled_payments' => self::ENABLED_PAYMENTS,
            'expiry' => [
                'start_time' => now()->format('Y-m-d H:i:s O'),
                'unit' => 'hours',
                'duration' => max((int) config('spmm.payment.invoice_expiry_hours'), 1),
            ],
        ]);

        return new InvoiceResult(
            gatewayReference: $orderId,
            paymentUrl: $result->redirect_url,
            paymentMethod: 'snap',
        );
    }

    public function validateWebhook(Request $request): bool
    {
        $orderId = (string) $request->input('order_id');
        $statusCode = (string) $request->input('status_code');
        $grossAmount = (string) $request->input('gross_amount');
        $signatureKey = (string) $request->input('signature_key');
        $serverKey = (string) config('spmm.payment.midtrans.server_key');

        $expectedSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        return hash_equals($expectedSignature, $signatureKey);
    }

    public function parseWebhook(Request $request): PaymentWebhookResult
    {
        $transactionStatus = (string) $request->input('transaction_status');

        return new PaymentWebhookResult(
            gatewayReference: (string) $request->input('order_id'),
            eventType: $transactionStatus,
            isPaid: in_array($transactionStatus, ['capture', 'settlement'], true),
            payload: $request->all(),
        );
    }

    public function getInvoiceStatus(Invoice $invoice): string
    {
        $status = Transaction::status($invoice->invoice_number);

        return (string) ($status->transaction_status ?? $invoice->status->value);
    }
}
