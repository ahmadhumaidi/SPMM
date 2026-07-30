<?php

namespace App\Filament\Resources\WhatsappBroadcastResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RecipientsRelationManager extends RelationManager
{
    protected static string $relationship = 'recipients';

    protected static ?string $title = 'Penerima';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('recipient_number')
            ->columns([
                TextColumn::make('recipient_name')->label('Nama')->default('-'),
                TextColumn::make('recipient_number')->label('Nomor'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'queued' => 'gray',
                        'sent' => 'success',
                        'failed' => 'danger',
                        'skipped' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('attempts')->label('Percobaan'),
                TextColumn::make('sent_at')->label('Terkirim')->dateTime('d M Y H:i')->default('-'),
                TextColumn::make('failed_reason')->label('Alasan Gagal')->default('-')->wrap(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'queued' => 'Antrian',
                        'sent' => 'Terkirim',
                        'failed' => 'Gagal',
                        'skipped' => 'Dilewati',
                    ]),
            ])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
