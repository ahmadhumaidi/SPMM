<?php

namespace App\Filament\Resources\WhatsappBroadcastResource\Pages;

use App\Filament\Resources\WhatsappBroadcastResource;
use Filament\Resources\Pages\CreateRecord;

class CreateWhatsappBroadcast extends CreateRecord
{
    protected static string $resource = WhatsappBroadcastResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_user_id'] = auth()->id();
        $data['template_name'] = 'manual_browser';
        $data['template_language'] = 'id';

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
