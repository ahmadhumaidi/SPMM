<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrashResource\Pages;
use App\Models\AuditLog;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Throwable;

class TrashResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-trash';

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Sampah';

    protected static ?string $modelLabel = 'Sampah';

    protected static ?string $pluralModelLabel = 'Sampah';

    protected static ?string $slug = 'sampah';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::isSuperAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Aktivitas hapus')
                ->schema([
                    TextInput::make('created_at')->label('Waktu')->disabled(),
                    TextInput::make('user.name')->label('User')->disabled(),
                    TextInput::make('metadata.label')->label('Data')->disabled(),
                    TextInput::make('auditable_type')->label('Model')->disabled(),
                    TextInput::make('auditable_id')->label('ID')->disabled(),
                    KeyValue::make('metadata')->label('Metadata')->disabled()->columnSpanFull(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->modifyQueryUsing(fn ($query) => $query->where('event', 'model_deleted')->with('user'))
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('created_at')
                    ->label('Waktu hapus')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('Dihapus oleh')
                    ->placeholder('Sistem')
                    ->searchable(),
                TextColumn::make('metadata.label')
                    ->label('Data')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('auditable_type')
                    ->label('Tipe')
                    ->formatStateUsing(fn (?string $state): string => class_basename($state ?? '-'))
                    ->searchable(),
                TextColumn::make('auditable_id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('restore_status')
                    ->label('Status restore')
                    ->badge()
                    ->state(fn (AuditLog $record): string => self::restoreStatus($record))
                    ->color(fn (string $state): string => match ($state) {
                        'Bisa direstore' => 'success',
                        'Sudah aktif' => 'info',
                        default => 'warning',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('auditable_type')
                    ->label('Tipe data')
                    ->options(fn (): array => AuditLog::query()
                        ->where('event', 'model_deleted')
                        ->whereNotNull('auditable_type')
                        ->distinct()
                        ->pluck('auditable_type', 'auditable_type')
                        ->mapWithKeys(fn (string $type, string $key): array => [$key => class_basename($type)])
                        ->all()),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->label('Detail'),
                Action::make('restore')
                    ->label('Restore')
                    ->icon('heroicon-o-arrow-path')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Restore data terhapus?')
                    ->modalDescription('Data akan dikembalikan berdasarkan record soft delete atau snapshot audit saat data dihapus.')
                    ->visible(fn (AuditLog $record): bool => self::canAttemptRestore($record))
                    ->action(fn (AuditLog $record): mixed => self::restoreDeletedRecord($record)),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    private static function restoreStatus(AuditLog $record): string
    {
        $class = self::modelClass($record);

        if ($class === null) {
            return 'Model tidak tersedia';
        }

        if (self::usesSoftDeletes($class)) {
            $model = $class::withTrashed()->whereKey($record->auditable_id)->first();

            if ($model?->trashed()) {
                return 'Bisa direstore';
            }

            if ($model !== null) {
                return 'Sudah aktif';
            }
        }

        return filled(data_get($record->metadata, 'restore_payload')) ? 'Bisa direstore' : 'Snapshot tidak ada';
    }

    private static function canAttemptRestore(AuditLog $record): bool
    {
        return in_array(self::restoreStatus($record), ['Bisa direstore', 'Sudah aktif'], true);
    }

    private static function restoreDeletedRecord(AuditLog $record): void
    {
        $class = self::modelClass($record);

        if ($class === null) {
            self::notifyError('Model data ini sudah tidak tersedia di sistem.');

            return;
        }

        try {
            if (self::usesSoftDeletes($class)) {
                $model = $class::withTrashed()->whereKey($record->auditable_id)->first();

                if ($model?->trashed()) {
                    $model->restore();
                    self::notifySuccess('Data berhasil direstore.');

                    return;
                }

                if ($model !== null) {
                    self::notifySuccess('Data sudah dalam kondisi aktif.');

                    return;
                }
            }

            $payload = data_get($record->metadata, 'restore_payload');

            if (! is_array($payload) || $payload === []) {
                self::notifyError('Snapshot data tidak tersedia, jadi data lama ini belum bisa direstore.');

                return;
            }

            /** @var Model $model */
            $model = new $class;
            $table = $model->getTable();
            $keyName = $model->getKeyName();

            $attributes = collect($payload)
                ->filter(fn (mixed $value, string $field): bool => Schema::hasColumn($table, $field))
                ->all();

            $attributes[$keyName] = $record->auditable_id;
            unset($attributes['deleted_at']);

            $existing = $class::query()->whereKey($record->auditable_id)->exists();

            if ($existing) {
                self::notifySuccess('Data sudah dalam kondisi aktif.');

                return;
            }

            Model::unguarded(function () use ($class, $attributes): void {
                $class::query()->create($attributes);
            });

            self::notifySuccess('Data berhasil direstore dari snapshot audit.');
        } catch (Throwable $exception) {
            report($exception);

            Notification::make()
                ->title('Restore gagal')
                ->body('Data tidak bisa direstore otomatis. Periksa relasi data atau file terkait terlebih dahulu.')
                ->danger()
                ->send();
        }
    }

    private static function modelClass(AuditLog $record): ?string
    {
        $class = data_get($record->metadata, 'model', $record->auditable_type);

        if (! is_string($class) || ! class_exists($class) || ! is_subclass_of($class, Model::class)) {
            return null;
        }

        return $class;
    }

    private static function usesSoftDeletes(string $class): bool
    {
        return in_array(SoftDeletes::class, class_uses_recursive($class), true);
    }

    private static function notifySuccess(string $message): void
    {
        Notification::make()->title($message)->success()->send();
    }

    private static function notifyError(string $message): void
    {
        Notification::make()->title($message)->danger()->send();
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTrash::route('/'),
        ];
    }
}
