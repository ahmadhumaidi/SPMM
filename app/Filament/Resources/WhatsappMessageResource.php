<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WhatsappMessageResource\Pages;
use App\Models\WhatsappMessage;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class WhatsappMessageResource extends Resource
{
    protected static ?string $model = WhatsappMessage::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Reports';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessReports();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('recipient_number')->disabled(),
            TextInput::make('template_key')->disabled(),
            TextInput::make('status')->disabled(),
            TextInput::make('provider_reference')->disabled(),
            DateTimePicker::make('sent_at')->disabled(),
            Textarea::make('message')->disabled()->columnSpanFull(),
            Textarea::make('failed_reason')->disabled()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('recipient_number')->searchable(),
                TextColumn::make('lead.full_name')->searchable()->placeholder('-'),
                TextColumn::make('template_key')->badge(),
                TextColumn::make('status')->badge(),
                TextColumn::make('sent_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('template_key')
                    ->options(fn (): array => WhatsappMessage::query()->distinct()->pluck('template_key', 'template_key')->all()),
                Tables\Filters\SelectFilter::make('status')
                    ->options(fn (): array => WhatsappMessage::query()->distinct()->pluck('status', 'status')->all()),
            ])
            ->actions([Tables\Actions\ViewAction::make()]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (FilamentResourceScope::isSuperAdmin()) {
            return $query;
        }

        return $query->whereHas('lead', fn (Builder $leadQuery): Builder => FilamentResourceScope::applyManagedLeadCampusScope($leadQuery));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWhatsappMessages::route('/'),
            'view' => Pages\ViewWhatsappMessage::route('/{record}'),
        ];
    }
}
