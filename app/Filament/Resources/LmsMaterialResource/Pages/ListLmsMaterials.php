<?php

namespace App\Filament\Resources\LmsMaterialResource\Pages;

use App\Filament\Resources\LmsMaterialResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLmsMaterials extends ListRecords
{
    protected static string $resource = LmsMaterialResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
