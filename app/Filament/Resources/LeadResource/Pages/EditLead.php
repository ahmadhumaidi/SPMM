<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use Filament\Resources\Pages\EditRecord;

class EditLead extends EditRecord
{
    protected static string $resource = LeadResource::class;

    protected function afterSave(): void
    {
        $lead = $this->record->fresh(['studentBiodata', 'studentNumber']);
        $biodata = $lead->studentBiodata;

        if (! $biodata) {
            return;
        }

        $biodata->update([
            'student_type' => $biodata->student_type ?: 'new',
            'campus_id' => $lead->campus_id,
            'study_program_id' => $biodata->study_program_id ?: $lead->study_program_id,
            'class_track_id' => $biodata->class_track_id ?: $lead->class_track_id,
            'name' => $biodata->name ?: $lead->full_name,
            'student_number' => $biodata->student_number ?: $lead->studentNumber?->nim,
            'financial_status' => str($lead->payment_status->value ?? $lead->payment_status)->replace('_', ' ')->title()->toString(),
            'email' => $biodata->email ?: $lead->email,
            'whatsapp_number' => $biodata->whatsapp_number ?: $lead->whatsapp_number,
        ]);

        $lead->update([
            'full_name' => $biodata->name ?: $lead->full_name,
            'email' => $biodata->email ?: $lead->email,
            'whatsapp_number' => $biodata->whatsapp_number ?: $lead->whatsapp_number,
            'study_program_id' => $biodata->study_program_id ?: $lead->study_program_id,
            'class_track_id' => $biodata->class_track_id ?: $lead->class_track_id,
        ]);
    }
}
