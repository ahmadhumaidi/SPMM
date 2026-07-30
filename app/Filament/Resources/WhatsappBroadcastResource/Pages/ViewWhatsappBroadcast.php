<?php

namespace App\Filament\Resources\WhatsappBroadcastResource\Pages;

use App\Filament\Resources\WhatsappBroadcastResource;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewWhatsappBroadcast extends ViewRecord
{
    protected static string $resource = WhatsappBroadcastResource::class;

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                TextEntry::make('name')->label('Nama Broadcast'),
                TextEntry::make('message_body')->label('Pesan')->columnSpanFull(),
                TextEntry::make('status')->label('Status')->badge(),
                TextEntry::make('recipient_count')->label('Total Penerima'),
                TextEntry::make('sent_count')->label('Terkirim'),
                TextEntry::make('failed_count')->label('Gagal'),
                TextEntry::make('queued_at')->label('Mulai Dikirim')->dateTime('d M Y H:i'),
                TextEntry::make('completed_at')->label('Selesai')->dateTime('d M Y H:i'),
            ]);
    }
}
