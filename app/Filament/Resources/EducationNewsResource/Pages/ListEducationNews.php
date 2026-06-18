<?php

namespace App\Filament\Resources\EducationNewsResource\Pages;

use App\Filament\Resources\EducationNewsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListEducationNews extends ListRecords
{
    protected static string $resource = EducationNewsResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
