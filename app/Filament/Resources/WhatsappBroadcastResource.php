<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsappBroadcastResource\Pages;
use App\Models\Lead;
use App\Models\WhatsappBroadcast;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WhatsappBroadcastResource extends Resource
{
    protected static ?string $model = WhatsappBroadcast::class;

    protected static ?string $navigationIcon = 'heroicon-o-paper-airplane';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'WA Broadcast';

    protected static ?string $modelLabel = 'WhatsApp Broadcast';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessStudentRecords();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nama Broadcast')
                    ->required()
                    ->maxLength(255),
                Select::make('lead_ids')
                    ->label('Pilih dari Lead/Mahasiswa')
                    ->multiple()
                    ->searchable()
                    ->getSearchResultsUsing(fn (string $search): array => Lead::query()
                        ->where(fn ($query) => $query
                            ->where('full_name', 'like', "%{$search}%")
                            ->orWhere('whatsapp_number', 'like', "%{$search}%"))
                        ->limit(50)
                        ->get()
                        ->mapWithKeys(fn (Lead $lead): array => [$lead->id => "{$lead->full_name} ({$lead->whatsapp_number})"])
                        ->all())
                    ->getOptionLabelsUsing(fn (array $values): array => Lead::query()
                        ->whereIn('id', $values)
                        ->get()
                        ->mapWithKeys(fn (Lead $lead): array => [$lead->id => "{$lead->full_name} ({$lead->whatsapp_number})"])
                        ->all()),
                Textarea::make('manual_numbers')
                    ->label('Atau tempel nomor manual')
                    ->helperText('Satu nomor per baris, contoh: 08123456789')
                    ->rows(4),
                FileUpload::make('recipients_file')
                    ->label('Atau upload file CSV/XLS/XLSX')
                    ->helperText('Kolom: nama, nomor, variabel 1, variabel 2, variabel 3. Baris pertama boleh header atau langsung data.')
                    ->disk('local')
                    ->directory('wa-bulk-imports')
                    ->visibility('private')
                    ->acceptedFileTypes([
                        'text/csv',
                        'text/plain',
                        'application/vnd.ms-excel',
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    ])
                    ->storeFiles()
                    ->maxSize(5120),
                Textarea::make('message_body')
                    ->label('Pesan')
                    ->required()
                    ->rows(6)
                    ->helperText('Pakai {{nama}} untuk nama penerima. Pakai {{1}}, {{2}}, {{3}} untuk isi kolom variabel 1/2/3 dari file upload.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('name')->label('Nama')->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'draft' => 'gray',
                        'queued', 'sending' => 'warning',
                        'completed' => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('recipient_count')->label('Penerima'),
                TextColumn::make('sent_count')->label('Terkirim')->color('success'),
                TextColumn::make('failed_count')->label('Gagal')->color('danger'),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i'),
            ])
            ->actions([
                Tables\Actions\Action::make('send')
                    ->label('Kirim Sekarang')
                    ->icon('heroicon-o-paper-airplane')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (WhatsappBroadcast $record): bool => $record->status === 'draft')
                    ->action(function (WhatsappBroadcast $record): void {
                        app(\App\Services\Whatsapp\WhatsappBroadcastService::class)->dispatch($record);
                    }),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [
            WhatsappBroadcastResource\RelationManagers\RecipientsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsappBroadcasts::route('/'),
            'create' => Pages\CreateWhatsappBroadcast::route('/create'),
            'view' => Pages\ViewWhatsappBroadcast::route('/{record}'),
        ];
    }
}
