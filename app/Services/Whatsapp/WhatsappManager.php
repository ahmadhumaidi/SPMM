<?php

namespace App\Services\Whatsapp;

use App\Services\Whatsapp\Contracts\WhatsappProvider;
use InvalidArgumentException;

class WhatsappManager
{
    public function driver(?string $provider = null): WhatsappProvider
    {
        return match ($provider ?? config('spmm.whatsapp.provider')) {
            'log' => app(LogWhatsappProvider::class),
            'n8n' => app(N8nWhatsappProvider::class),
            default => throw new InvalidArgumentException('Unsupported WhatsApp provider.'),
        };
    }
}
