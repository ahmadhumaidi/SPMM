<?php

namespace App\Filament\Resources\ExternalLeadEventResource\Pages;

use App\Filament\Resources\ExternalLeadEventResource;
use App\Services\MetaLeadImportService;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListExternalLeadEvents extends ListRecords
{
    protected static string $resource = ExternalLeadEventResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('importMetaForm')
                ->label('Import Lead Meta')
                ->icon('heroicon-o-arrow-down-tray')
                ->modalHeading('Import Lead Lama dari Form Meta')
                ->form([
                    TextInput::make('form_id')
                        ->label('Form ID Meta')
                        ->helperText('Contoh: 1421182943367763')
                        ->required()
                        ->numeric(),
                    TextInput::make('limit')
                        ->label('Batas jumlah lead')
                        ->numeric()
                        ->default(100)
                        ->minValue(1)
                        ->maxValue(500),
                ])
                ->action(function (array $data, MetaLeadImportService $importer): void {
                    $result = $importer->importForm((string) $data['form_id'], (int) ($data['limit'] ?? 100));

                    Notification::make()
                        ->title('Import lead Meta selesai')
                        ->body("Dibaca: {$result['seen']}. Baru: {$result['processed']}. Duplikat: {$result['duplicates']}. Gagal: {$result['failed']}.")
                        ->success()
                        ->send();
                }),
            Actions\Action::make('importMetaLead')
                ->label('Import 1 Lead')
                ->icon('heroicon-o-document-arrow-down')
                ->modalHeading('Import Satu Lead Meta')
                ->form([
                    TextInput::make('leadgen_id')
                        ->label('Leadgen ID')
                        ->helperText('Contoh: 1796297438001038')
                        ->required()
                        ->numeric(),
                ])
                ->action(function (array $data, MetaLeadImportService $importer): void {
                    $event = $importer->importLead((string) $data['leadgen_id']);

                    Notification::make()
                        ->title('Import lead Meta selesai')
                        ->body("Status: {$event->status}.")
                        ->success()
                        ->send();
                }),
        ];
    }
}
