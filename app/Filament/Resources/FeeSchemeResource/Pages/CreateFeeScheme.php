<?php

namespace App\Filament\Resources\FeeSchemeResource\Pages;

use App\Filament\Resources\FeeSchemeResource;
use App\Models\FeeScheme;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateFeeScheme extends CreateRecord
{
    protected static string $resource = FeeSchemeResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $classTrackIds = $data['class_track_ids'] ?? [];
        $studyProgramIds = $data['study_program_ids'] ?? [];
        unset($data['class_track_ids'], $data['study_program_ids']);

        $data = FeeSchemeResource::mutateFeeData($data);

        $firstRecord = null;
        $studyProgramIds = $studyProgramIds === [] ? [null] : $studyProgramIds;
        $classTrackIds = $classTrackIds === [] ? [null] : $classTrackIds;

        foreach ($studyProgramIds as $studyProgramId) {
            foreach ($classTrackIds as $classTrackId) {
                $record = FeeScheme::query()->create(array_merge($data, [
                    'study_program_id' => $studyProgramId,
                    'class_track_id' => $classTrackId,
                ]));

                $firstRecord ??= $record;
            }
        }

        return $firstRecord ?? FeeScheme::query()->create($data);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
