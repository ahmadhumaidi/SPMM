<?php

namespace App\Filament\Resources\AffiliateNetworkResource\Pages;

use App\Filament\Resources\AffiliateNetworkResource;
use App\Support\FilamentResourceScope;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAffiliateNetworks extends ListRecords
{
    protected static string $resource = AffiliateNetworkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('visual')
                ->label('Visual Network')
                ->icon('heroicon-o-squares-2x2')
                ->url(static::getResource()::getUrl('visual')),
            Actions\CreateAction::make()
                ->visible(fn (): bool => ! FilamentResourceScope::isDirector()),
        ];
    }
}