<?php

namespace App\Filament\Resources\NewStudentBiodataResource\Pages;

use App\Filament\Resources\NewStudentBiodataResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListNewStudentBiodatas extends ListRecords
{
    protected static string $resource = NewStudentBiodataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
