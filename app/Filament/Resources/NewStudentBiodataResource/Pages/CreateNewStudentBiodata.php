<?php

namespace App\Filament\Resources\NewStudentBiodataResource\Pages;

use App\Filament\Resources\NewStudentBiodataResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNewStudentBiodata extends CreateRecord
{
    protected static string $resource = NewStudentBiodataResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['student_type'] = 'new';

        return $data;
    }
}
