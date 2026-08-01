<?php

namespace App\Filament\Resources\AffiliateNetworkResource\Pages;

use App\Filament\Resources\AffiliateNetworkResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAffiliateNetwork extends CreateRecord
{
    protected static string $resource = AffiliateNetworkResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_user_id'] = auth()->id();

        return $data;
    }
}