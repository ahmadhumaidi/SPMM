<?php

namespace App\Filament\Resources\PartnerCampusSiteResource\Pages;

use App\Filament\Resources\PartnerCampusSiteResource;
use Filament\Resources\Pages\EditRecord;

class EditPartnerCampusSite extends EditRecord
{
    protected static string $resource = PartnerCampusSiteResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
