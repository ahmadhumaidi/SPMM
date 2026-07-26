<?php

namespace App\Filament\Resources\StudentNumberResource\Pages;

use App\Enums\EnrollmentStatus;
use App\Filament\Resources\StudentNumberResource;
use App\Services\SiakadStudentSyncService;
use Filament\Resources\Pages\CreateRecord;

class CreateStudentNumber extends CreateRecord
{
    protected static string $resource = StudentNumberResource::class;

    protected function afterCreate(): void
    {
        $this->record->lead->update([
            'enrollment_status' => EnrollmentStatus::MahasiswaAktif,
            'locked_at' => $this->record->lead->locked_at ?? now(),
        ]);

        app(\App\Services\AuditLogger::class)->record('nim_issued', $this->record, ['lead_id' => $this->record->lead_id]);
        app(\App\Services\AuditLogger::class)->record('enrollment_status_changed', $this->record->lead, ['to' => EnrollmentStatus::MahasiswaAktif->value]);

        app(SiakadStudentSyncService::class)->syncActivation($this->record->fresh());
    }
}
