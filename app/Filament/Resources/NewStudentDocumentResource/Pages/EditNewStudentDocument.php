<?php

namespace App\Filament\Resources\NewStudentDocumentResource\Pages;

use App\Filament\Resources\NewStudentDocumentResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNewStudentDocument extends EditRecord
{
    protected static string $resource = NewStudentDocumentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
