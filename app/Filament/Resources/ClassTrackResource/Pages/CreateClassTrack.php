<?php

namespace App\Filament\Resources\ClassTrackResource\Pages;

use App\Filament\Resources\ClassTrackResource;
use App\Models\ClassTrack;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateClassTrack extends CreateRecord
{
    protected static string $resource = ClassTrackResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        $names = $data['names'] ?? [];
        unset($data['names']);

        $firstRecord = null;

        foreach ($names as $name) {
            $record = ClassTrack::query()->updateOrCreate(
                [
                    'campus_id' => $data['campus_id'],
                    'name' => $name,
                ],
                [
                    'status' => $data['status'],
                    'description' => $data['description'] ?? null,
                ]
            );

            $firstRecord ??= $record;
        }

        return $firstRecord ?? ClassTrack::query()->create([
            'campus_id' => $data['campus_id'],
            'name' => reset($names),
            'status' => $data['status'],
            'description' => $data['description'] ?? null,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
