<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentEventResource\Pages;
use App\Models\PaymentEvent;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentEventResource extends Resource
{
    protected static ?string $model = PaymentEvent::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Payment';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessPayments();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('invoice.invoice_number')->label('Invoice')->disabled(),
            TextInput::make('gateway_reference')->disabled(),
            TextInput::make('event_type')->disabled(),
            DateTimePicker::make('processed_at')->disabled(),
            KeyValue::make('payload_json')->label('Payload')->disabled()->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('invoice.invoice_number')->searchable()->placeholder('-'),
                TextColumn::make('gateway_reference')->searchable(),
                TextColumn::make('event_type')->badge(),
                TextColumn::make('processed_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event_type')
                    ->options(fn (): array => PaymentEvent::query()->distinct()->pluck('event_type', 'event_type')->all()),
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

        return $query->whereHas('invoice.lead', fn (Builder $leadQuery): Builder => FilamentResourceScope::applyManagedLeadCampusScope($leadQuery));
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentEvents::route('/'),
            'view' => Pages\ViewPaymentEvent::route('/{record}'),
        ];
    }
}
