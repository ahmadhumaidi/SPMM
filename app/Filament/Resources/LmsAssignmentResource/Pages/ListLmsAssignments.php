<?php

namespace App\Filament\Resources\LmsAssignmentResource\Pages;

use App\Filament\Resources\LmsAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLmsAssignments extends ListRecords
{
    protected static string $resource = LmsAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
