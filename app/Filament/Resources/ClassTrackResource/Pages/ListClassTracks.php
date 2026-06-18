<?php

namespace App\Filament\Resources\ClassTrackResource\Pages;

use App\Filament\Resources\ClassTrackResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClassTracks extends ListRecords
{
    protected static string $resource = ClassTrackResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
