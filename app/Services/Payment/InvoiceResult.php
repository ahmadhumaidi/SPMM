<?php

namespace App\Services\Payment;

class InvoiceResult
{
    public function __construct(
        public readonly string $gatewayReference,
        public readonly ?string $paymentUrl,
        public readonly ?string $paymentMethod = null,
        public readonly ?string $qrString = null,
        public readonly ?string $vaNumber = null,
    ) {
    }
}
