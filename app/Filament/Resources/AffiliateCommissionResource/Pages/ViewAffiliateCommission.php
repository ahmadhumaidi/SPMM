<?php

namespace App\Filament\Resources\AffiliateCommissionResource\Pages;

use App\Filament\Resources\AffiliateCommissionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAffiliateCommission extends ViewRecord
{
    protected static string $resource = AffiliateCommissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('back')
                ->label('Kembali')
                ->url(static::getResource()::getUrl('index'))
                ->icon('heroicon-o-arrow-left'),
        ];
    }
}
