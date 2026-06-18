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
                TextColumn::make('event')->badge()->searchable(),
                TextColumn::make('user.name')->placeholder('System')->searchable(),
                TextColumn::make('auditable_type')->label('Model')->formatStateUsing(fn (?string $state): string => class_basename($state ?? '-')),
                TextColumn::make('auditable_id')->label('ID'),
                TextColumn::make('created_at')->dateTime()->sortable(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
            'view' => Pages\ViewAuditLog::route('/{record}'),
        ];
    }
}
