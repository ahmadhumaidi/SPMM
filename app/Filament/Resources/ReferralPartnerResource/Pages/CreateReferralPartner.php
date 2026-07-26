<?php

namespace App\Filament\Resources\ReferralPartnerResource\Pages;

use App\Filament\Resources\ReferralPartnerResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;

class CreateReferralPartner extends CreateRecord
{
    protected static string $resource = ReferralPartnerResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (($data['type'] ?? null) === 'umum') {
            $data['dashboard_token'] = Str::random(64);
        }

        return $data;
    }
}
