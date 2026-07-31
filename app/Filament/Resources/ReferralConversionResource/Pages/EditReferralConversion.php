<?php

namespace App\Filament\Resources\ReferralConversionResource\Pages;

use App\Filament\Resources\ReferralConversionResource;
use Filament\Resources\Pages\EditRecord;

class EditReferralConversion extends EditRecord
{
    protected static string $resource = ReferralConversionResource::class;

    protected function afterSave(): void
    {
        ReferralConversionResource::refreshOverallCommissionStatus($this->record->fresh());
    }
}
