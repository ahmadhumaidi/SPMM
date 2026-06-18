<?php

namespace App\Filament\Resources\StudyProgramResource\Pages;

use App\Filament\Resources\StudyProgramResource;
use Filament\Resources\Pages\EditRecord;

class EditStudyProgram extends EditRecord
{
    protected static string $resource = StudyProgramResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['program_catalog_key'], $data['program_catalog_keys']);

        return $data;
    }
}
