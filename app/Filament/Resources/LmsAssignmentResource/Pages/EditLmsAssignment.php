<?php

namespace App\Filament\Resources\LmsAssignmentResource\Pages;

use App\Filament\Resources\LmsAssignmentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLmsAssignment extends EditRecord
{
    protected static string $resource = LmsAssignmentResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
