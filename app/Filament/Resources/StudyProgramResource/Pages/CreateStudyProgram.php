<?php

namespace App\Filament\Resources\StudyProgramResource\Pages;

use App\Filament\Resources\StudyProgramResource;
use App\Models\StudyProgram;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class CreateStudyProgram extends CreateRecord
{
    protected static string $resource = StudyProgramResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $inputMode = $data['input_mode'] ?? 'catalog';
        $programKeys = $data['program_catalog_keys'] ?? [];

        if ($inputMode === 'manual') {
            if (($data['faculty_choice'] ?? null) !== '__manual') {
                $data['faculty'] = $data['faculty_choice'] ?? $data['faculty'] ?? null;
            }

            unset($data['input_mode'], $data['program_catalog_keys'], $data['program_catalog_key'], $data['faculty_choice']);

            $data['code'] = filled($data['code'] ?? null)
                ? $data['code']
                : Str::slug(($data['degree_level'] ?? '').' '.($data['faculty'] ?? '').' '.($data['name'] ?? ''));

            return StudyProgram::query()->updateOrCreate(
                [
                    'campus_id' => $data['campus_id'],
                    'code' => $data['code'],
                ],
                $data
            );
        }

        unset($data['input_mode'], $data['program_catalog_keys'], $data['program_catalog_key'], $data['faculty_choice'], $data['code'], $data['name'], $data['degree_level'], $data['faculty']);

        $firstRecord = null;

        foreach ($programKeys as $programKey) {
            $item = StudyProgramResource::findProgramOption($programKey);

            if (! $item) {
                continue;
            }

            $record = StudyProgram::query()->updateOrCreate(
                [
                    'campus_id' => $data['campus_id'],
                    'code' => $item['code'] ?? $programKey,
                ],
                array_merge($data, [
                    'code' => $item['code'] ?? $programKey,
                    'name' => $item['name'],
                    'degree_level' => $item['degree_level'],
                    'faculty' => $item['faculty'],
                ])
            );

            $firstRecord ??= $record;
        }

        return $firstRecord ?? StudyProgram::query()->create($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
