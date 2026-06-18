<?php

namespace App\Filament\Resources\FeeSchemeResource\Pages;

use App\Filament\Resources\FeeSchemeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListFeeSchemes extends ListRecords
{
    protected static string $resource = FeeSchemeResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
