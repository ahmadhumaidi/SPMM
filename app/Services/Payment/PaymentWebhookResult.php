<?php

namespace App\Services\Payment;

class PaymentWebhookResult
{
    public function __construct(
        public readonly string $gatewayReference,
        public readonly string $eventType,
        public readonly bool $isPaid,
        public readonly array $payload,
    ) {
    }
}
