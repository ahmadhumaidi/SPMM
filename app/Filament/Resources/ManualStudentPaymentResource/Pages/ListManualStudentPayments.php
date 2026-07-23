<?php

namespace App\Filament\Resources\ManualStudentPaymentResource\Pages;

use App\Filament\Resources\ManualStudentPaymentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListManualStudentPayments extends ListRecords
{
    protected static string $resource = ManualStudentPaymentResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()->label('Tambah Pembayaran Manual')];
    }
}
