<?php

namespace App\Filament\Resources\OldStudentBiodataResource\Pages;

use App\Filament\Resources\OldStudentBiodataResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOldStudentBiodata extends CreateRecord
{
    protected static string $resource = OldStudentBiodataResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['student_type'] = 'old';

        return $data;
    }
}
