<?php

namespace App\Filament\Resources\StudentPaymentResource\Pages;

use App\Filament\Resources\StudentPaymentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewStudentPayment extends ViewRecord
{
    protected static string $resource = StudentPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
