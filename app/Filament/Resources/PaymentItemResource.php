<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentItemResource\Pages;
use App\Models\Campus;
use App\Models\PaymentItem;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PaymentItemResource extends Resource
{
    protected static ?string $model = PaymentItem::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Payment';
    protected static ?string $navigationLabel = 'Item Pembayaran';
    protected static ?string $modelLabel = 'Item Pembayaran';
    protected static ?string $pluralModelLabel = 'Item Pembayaran';
    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('campus_id')
                ->label('Kampus')
                ->options(fn (): array => FilamentResourceScope::applyCampusScope(Campus::query()->orderBy('name'))->pluck('name', 'id')->all())
                ->searchable()
                ->preload()
                ->helperText('Kosongkan jika item berlaku untuk semua kampus.'),
            TextInput::make('name')->label('Nama item')->required()->maxLength(255),
            TextInput::make('default_amount')->label('Nominal default')->numeric()->prefix('Rp')->default(0)->required(),
            Toggle::make('is_active')->label('Aktif')->default(true),
            Textarea::make('description')->label('Keterangan')->columnSpanFull(),
        ])->columns(2);
    }

    public static function getEloquentQuery(): Builder
    {
        return FilamentResourceScope::applyCampusScope(parent::getEloquentQuery());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('name')->label('Item')->searchable()->sortable(),
                TextColumn::make('campus.name')->label('Kampus')->placeholder('Semua kampus')->searchable(),
                TextColumn::make('default_amount')->label('Nominal default')->money('IDR')->sortable()->alignEnd(),
                IconColumn::make('is_active')->label('Aktif')->boolean(),
                TextColumn::make('created_at')->label('Dibuat')->dateTime()->sortable(),
            ])
            ->actions([Tables\Actions\EditAction::make(), Tables\Actions\DeleteAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentItems::route('/'),
            'create' => Pages\CreatePaymentItem::route('/create'),
            'edit' => Pages\EditPaymentItem::route('/{record}/edit'),
        ];
    }
}
