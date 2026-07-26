<?php

namespace App\Filament\Resources\StudentPaymentResource\Pages;

use App\Filament\Resources\StudentPaymentResource;
use Filament\Resources\Pages\EditRecord;

class EditStudentPayment extends EditRecord
{
    protected static string $resource = StudentPaymentResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
