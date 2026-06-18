<?php

namespace App\Filament\Resources\OldStudentBiodataResource\Pages;

use App\Filament\Resources\OldStudentBiodataResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOldStudentBiodatas extends ListRecords
{
    protected static string $resource = OldStudentBiodataResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
