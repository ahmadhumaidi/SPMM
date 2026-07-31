<?php

namespace App\Filament\Resources\WhatsappBroadcastResource\Pages;

use App\Filament\Resources\WhatsappBroadcastResource;
use Filament\Resources\Pages\EditRecord;

class EditWhatsappBroadcast extends EditRecord
{
    protected static string $resource = WhatsappBroadcastResource::class;

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
