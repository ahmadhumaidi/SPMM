<?php

namespace App\Filament\Resources\ExternalLeadEventResource\Pages;

use App\Filament\Resources\ExternalLeadEventResource;
use App\Jobs\ImportMetaFormLeadsJob;
use Filament\Actions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
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
                        ->default(fn (): ?string => config('spmm.meta_leads.form_id'))
                        ->required()
                        ->numeric(),
                    TextInput::make('limit')
                        ->label('Batas jumlah lead')
                        ->numeric()
                        ->default(100)
                        ->minValue(1)
                        ->maxValue(1000),
                    Toggle::make('reprocess_existing')
                        ->label('Proses ulang lead yang sudah pernah diimport')
                        ->helperText('Aktifkan jika mapping kampus/prodi sebelumnya salah. Sistem akan memperbaiki lead lama, bukan membuat double.')
                        ->default(true),
                ])
                ->action(function (array $data): void {
                    ImportMetaFormLeadsJob::dispatch(
                        (string) $data['form_id'],
                        (int) ($data['limit'] ?? 100),
                        auth()->id(),
                        (bool) ($data['reprocess_existing'] ?? false),
                    );

                    Notification::make()
                        ->title('Import lead Meta diproses')
                        ->body('Import berjalan di background. Refresh halaman ini beberapa saat lagi untuk melihat status event dan lead yang masuk.')
                        ->success()
                        ->send();
                }),
        ];
    }
}
