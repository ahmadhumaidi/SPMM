<?php

namespace App\Filament\Resources\FeeSchemeResource\Pages;

use App\Filament\Resources\FeeSchemeResource;
use Filament\Resources\Pages\EditRecord;

class EditFeeScheme extends EditRecord
{
    protected static string $resource = FeeSchemeResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['development_installment_terms'] = collect($data['development_installments_json'] ?? [])
            ->pluck('term')
            ->map(fn ($term): int => (int) $term)
            ->values()
            ->all();

        $data['tuition_installment_terms'] = collect($data['tuition_installments_json'] ?? [])
            ->pluck('term')
            ->map(fn ($term): int => (int) $term)
            ->values()
            ->all();

        $data['ukt_installment_terms'] = collect($data['ukt_installments_json'] ?? [])
            ->pluck('term')
            ->map(fn ($term): int => (int) $term)
            ->values()
            ->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        unset($data['class_track_ids'], $data['study_program_ids']);

        if (! FeeSchemeResource::isSuperAdmin()) {
            $data['uses_custom_installments'] = $this->record->uses_custom_installments;
            $data['custom_installment_schedule_json'] = $this->record->custom_installment_schedule_json;
        }

        return FeeSchemeResource::mutateFeeData($data);
    }
}
