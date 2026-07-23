<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Enums\EnrollmentStatus;
use App\Enums\LeadStatus;
use App\Enums\PaymentStatus;
use App\Filament\Resources\LeadResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLead extends CreateRecord
{
    protected static string $resource = LeadResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['source_channel'] = $data['source_channel'] ?? 'manual';
        $data['source_detail'] = $data['source_detail'] ?: 'Input manual dari dashboard';
        $data['lead_status'] = $data['lead_status'] ?? LeadStatus::InPool->value;
        $data['payment_status'] = $data['payment_status'] ?? PaymentStatus::Pending->value;
        $data['enrollment_status'] = $data['enrollment_status'] ?? EnrollmentStatus::CalonMahasiswa->value;

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
