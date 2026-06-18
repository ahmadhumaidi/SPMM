<?php

namespace App\Filament\Resources\LmsModuleResource\Pages;

use App\Filament\Resources\LmsModuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLmsModules extends ListRecords
{
    protected static string $resource = LmsModuleResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
