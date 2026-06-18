<?php

namespace App\Services\Payment;

use App\Services\Payment\Contracts\PaymentGateway;
use InvalidArgumentException;

class PaymentGatewayManager
{
    public function driver(?string $provider = null): PaymentGateway
    {
        return match ($provider ?? config('spmm.payment.provider')) {
            'mock' => app(MockPaymentGateway::class),
            default => throw new InvalidArgumentException('Unsupported payment provider.'),
        };
    }
}
