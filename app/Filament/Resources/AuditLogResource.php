<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AuditLogResource extends Resource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    protected static ?string $navigationGroup = 'Reports';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::isSuperAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('event')->disabled(),
            TextInput::make('auditable_type')->disabled(),
            TextInput::make('auditable_id')->disabled(),
            TextInput::make('user.name')->label('User')->disabled(),
            KeyValue::make('metadata')->disabled()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('event')
                    ->label('Aktivitas')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::eventLabel($state))
                    ->searchable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('Sistem')
                    ->searchable(),
                TextColumn::make('auditable_type')->label('Model')->formatStateUsing(fn (?string $state): string => class_basename($state ?? '-')),
                TextColumn::make('metadata.label')->label('Data')->searchable(),
                TextColumn::make('auditable_id')->label('ID'),
                TextColumn::make('created_at')->label('Waktu')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->options(fn (): array => AuditLog::query()->distinct()->pluck('event', 'event')->all()),
            ])
            ->actions([Tables\Actions\ViewAction::make()]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    private static function eventLabel(string $event): string
    {
        return match ($event) {
            'model_created' => 'Data dibuat',
            'model_updated' => 'Data diubah',
            'model_deleted' => 'Data dihapus',
            'lead_created' => 'Lead dibuat',
            'lead_assigned' => 'Lead dibagikan',
            'invoice_created' => 'Invoice dibuat',
            'invoice_paid' => 'Invoice lunas',
            'invoice_expired' => 'Invoice expired',
            'invoice_regenerated' => 'Invoice dibuat ulang',
            'student_profile_completed' => 'Biodata selesai',
            'enrollment_status_changed' => 'Status enrollment berubah',
            'nim_issued' => 'NIM diterbitkan',
            default => Str::headline(str_replace('_', ' ', $event)),
        };
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}
