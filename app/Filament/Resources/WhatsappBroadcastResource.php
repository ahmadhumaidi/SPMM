<?php

namespace App\Filament\Resources;

use App\Enums\LeadStatus;
use App\Filament\Resources\WhatsappBroadcastResource\Pages;
use App\Models\Campus;
use App\Models\WhatsappBroadcast;
use App\Services\Whatsapp\WhatsappBroadcastService;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\Actions as FormActions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class WhatsappBroadcastResource extends Resource
{
    protected static ?string $model = WhatsappBroadcast::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'CRM';

    protected static ?string $navigationLabel = 'Broadcast WhatsApp';

    protected static ?string $modelLabel = 'Broadcast WhatsApp';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessMasterData();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label('Nama broadcast')->required()->maxLength(255),
            Select::make('campus_id')
                ->label('Kampus')
                ->options(fn (): array => FilamentResourceScope::applyCampusScope(Campus::query()->orderBy('name'), 'id')->pluck('name', 'id')->all())
                ->searchable()
                ->placeholder('Semua kampus'),
            Select::make('lead_status')
                ->label('Status lead')
                ->options(collect(LeadStatus::cases())->mapWithKeys(fn (LeadStatus $status) => [$status->value => str($status->value)->replace('_', ' ')->title()->toString()])->all())
                ->placeholder('Semua status'),
            FileUpload::make('recipients_file_path')
                ->label('Atau upload nomor dari file CSV/XLS/XLSX')
                ->helperText('Kolom: nama, nomor. Baris pertama boleh header atau langsung data. Ditambahkan sebagai penerima tambahan di luar filter kampus/status di atas.')
                ->disk('local')
                ->directory('wa-broadcast-imports')
                ->visibility('private')
                ->acceptedFileTypes([
                    'text/csv',
                    'text/plain',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ])
                ->columnSpanFull()
                ->maxSize(5120),
            FormActions::make([
                static::messageTokenAction('nama', 'Nama', '{nama}'),
                static::messageTokenAction('nomor', 'Nomor', '{nomor}'),
                static::messageTokenAction('jurusan', 'Jurusan', '{jurusan}'),
                static::messageTokenAction('tagihan', 'Tagihan', '{tagihan}'),
            ])
                ->label('Sisipkan data otomatis')
                ->columnSpanFull(),
            Textarea::make('message_body')
                ->label('Isi pesan')
                ->helperText('Pesan akan dibuka otomatis di WhatsApp Web, lalu admin klik kirim.')
                ->required()
                ->rows(6)
                ->columnSpanFull(),
            TextInput::make('interval_seconds')
                ->label('Interval antar kontak (detik)')
                ->numeric()
                ->minValue(10)
                ->default(45)
                ->required(),
            TextInput::make('max_recipients')
                ->label('Maksimal penerima per sesi')
                ->numeric()
                ->minValue(1)
                ->default(50)
                ->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table->defaultSort('created_at', 'desc')->columns([
            TextColumn::make('name')->label('Broadcast')->searchable(),
            TextColumn::make('message_body')->label('Pesan')->limit(42)->toggleable(),
            TextColumn::make('status')
                ->label('Status')
                ->badge()
                ->formatStateUsing(fn (WhatsappBroadcast $record): string => $record->sent_count > 0 ? 'Terkirim' : 'Tidak terkirim')
                ->color(fn (WhatsappBroadcast $record): string => $record->sent_count > 0 ? 'success' : 'danger'),
            TextColumn::make('recipient_count')->label('Penerima'),
            TextColumn::make('sent_count')->label('Terkirim'),
            TextColumn::make('failed_count')->label('Gagal/Invalid'),
            TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable(),
        ])->actions([
            Tables\Actions\Action::make('send')
                ->label('Antrekan kirim')
                ->icon('heroicon-o-paper-airplane')
                ->color('success')
                ->requiresConfirmation()
                ->modalDescription('Kontak dengan nomor WhatsApp akan dimasukkan ke antrean manual WhatsApp Web.')
                ->visible(fn (WhatsappBroadcast $record): bool => $record->status === 'draft')
                ->action(function (WhatsappBroadcast $record): void {
                    try {
                        $count = app(WhatsappBroadcastService::class)->queue($record);
                        Notification::make()->title("{$count} pesan masuk antrean")->success()->send();
                    } catch (Throwable $exception) {
                        Notification::make()->title('Broadcast tidak dapat dikirim')->body($exception->getMessage())->danger()->send();
                    }
                }),
            Tables\Actions\Action::make('open_whatsapp')
                ->label('Mulai WhatsApp Web')
                ->icon('heroicon-o-chat-bubble-left-right')
                ->color('info')
                ->url(fn (WhatsappBroadcast $record): string => route('admin.whatsapp-broadcasts.manual-runner', $record))
                ->openUrlInNewTab()
                ->visible(fn (WhatsappBroadcast $record): bool => $record->status === 'queued' && filled($record->nextWhatsappWebUrl())),
            Tables\Actions\Action::make('send_via_fonnte')
                ->label('Kirim via Fonnte')
                ->icon('heroicon-o-bolt')
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('Pesan akan dikirim otomatis lewat Fonnte (tanpa perlu buka WhatsApp Web manual). Butuh device Fonnte dalam keadaan terhubung.')
                ->visible(fn (WhatsappBroadcast $record): bool => in_array($record->status, ['queued', 'sending'], true) && $record->recipients()->where('status', 'queued')->exists())
                ->action(function (WhatsappBroadcast $record): void {
                    $ids = $record->recipients()->where('status', 'queued')->pluck('id');

                    foreach ($ids as $index => $id) {
                        \App\Jobs\SendWhatsappBroadcastRecipientViaFonnte::dispatch($id)->delay(now()->addSeconds($index * 3));
                    }

                    Notification::make()->title(count($ids).' pesan diantrekan lewat Fonnte')->success()->send();
                }),
            Tables\Actions\Action::make('report')
                ->label('Report')
                ->icon('heroicon-o-chart-bar-square')
                ->color('gray')
                ->url(fn (WhatsappBroadcast $record): string => route('admin.whatsapp-broadcasts.report', $record))
                ->openUrlInNewTab(),
            Tables\Actions\EditAction::make()->visible(fn (WhatsappBroadcast $record): bool => $record->status === 'draft'),
            Tables\Actions\DeleteAction::make()
                ->label('Hapus')
                ->modalHeading('Hapus broadcast')
                ->modalDescription('Broadcast dan data penerimanya akan ikut dihapus. Riwayat yang sudah terkirim tidak bisa dibatalkan.')
                ->modalSubmitActionLabel('Ya, hapus')
                ->modalCancelActionLabel('Batal'),
        ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Hapus Terpilih')
                    ->modalHeading('Hapus broadcast terpilih')
                    ->modalDescription('Semua broadcast yang dicentang beserta data penerimanya akan dihapus.')
                    ->modalSubmitActionLabel('Ya, hapus')
                    ->modalCancelActionLabel('Batal'),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return FilamentResourceScope::applyCampusScope(parent::getEloquentQuery());
    }

    private static function messageTokenAction(string $name, string $label, string $token): FormAction
    {
        return FormAction::make('insert_'.$name)
            ->label($label)
            ->button()
            ->extraAttributes([
                'style' => 'background: linear-gradient(135deg, #dc2626 0%, #7A1220 100%); color: #ffffff; border: 0;',
            ])
            ->action(function (Get $get, Set $set) use ($token): void {
                $message = trim((string) $get('message_body'));
                $set('message_body', trim($message.' '.$token));
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsappBroadcasts::route('/'),
            'create' => Pages\CreateWhatsappBroadcast::route('/create'),
            'edit' => Pages\EditWhatsappBroadcast::route('/{record}/edit'),
        ];
    }
}
