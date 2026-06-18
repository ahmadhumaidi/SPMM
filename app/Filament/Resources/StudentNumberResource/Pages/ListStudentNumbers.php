<?php

namespace App\Filament\Resources\StudentNumberResource\Pages;

use App\Filament\Resources\StudentNumberResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListStudentNumbers extends ListRecords
{
    protected static string $resource = StudentNumberResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
