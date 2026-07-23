<?php

namespace App\Filament\Resources\PaymentItemResource\Pages;

use App\Filament\Resources\PaymentItemResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePaymentItem extends CreateRecord
{
    protected static string $resource = PaymentItemResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
