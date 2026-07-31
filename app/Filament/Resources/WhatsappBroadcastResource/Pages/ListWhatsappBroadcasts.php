<?php

namespace App\Filament\Resources\WhatsappBroadcastResource\Pages;

use App\Filament\Resources\WhatsappBroadcastResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWhatsappBroadcasts extends ListRecords
{
    protected static string $resource = WhatsappBroadcastResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('download_template_csv')
                ->label('Contoh CSV')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(asset('templates/contoh-broadcast-whatsapp.csv'))
                ->openUrlInNewTab(),
            Actions\Action::make('download_template_xlsx')
                ->label('Contoh XLSX')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('gray')
                ->url(asset('templates/contoh-broadcast-whatsapp.xlsx'))
                ->openUrlInNewTab(),
            Actions\CreateAction::make(),
        ];
    }
}
