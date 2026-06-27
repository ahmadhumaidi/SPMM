<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralConversionResource\Pages;
use App\Models\ReferralConversion;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ReferralConversionResource extends Resource
{
    protected static ?string $model = ReferralConversion::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Afiliasi';

    protected static ?string $navigationLabel = 'Konversi Referral';

    protected static ?string $modelLabel = 'Konversi Referral';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessAffiliates();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('referralPartner.name')->label('Partner')->disabled(),
            TextInput::make('lead.full_name')->label('Pendaftar')->disabled(),
            DateTimePicker::make('registered_at')->label('Tanggal daftar'),
            DateTimePicker::make('paid_at')->label('Tanggal bayar'),
            DateTimePicker::make('active_at')->label('Tanggal aktif'),
            TextInput::make('commission_amount')->label('Komisi')->prefix('Rp')->numeric(),
            Select::make('commission_status')
                ->label('Status komisi')
                ->options([
                    'pending' => 'Pending',
                    'approved' => 'Approved',
                    'paid' => 'Paid',
                    'cancelled' => 'Cancelled',
                ])
                ->required(),
        ])->columns(2);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['lead.campus', 'referralPartner']);

        if (FilamentResourceScope::isSuperAdmin()) {
            return $query;
        }

        return $query->whereHas('lead', fn (Builder $leadQuery): Builder => FilamentResourceScope::applyManagedLeadCampusScope($leadQuery));
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('referralPartner.name')->label('Partner')->searchable(),
                TextColumn::make('referralPartner.referral_code')->label('Kode')->searchable(),
                TextColumn::make('lead.full_name')->label('Pendaftar')->searchable(),
                TextColumn::make('lead.campus.name')->label('Kampus'),
                TextColumn::make('lead.studyProgram.name')->label('Prodi'),
                TextColumn::make('registered_at')->label('Daftar')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('paid_at')->label('Bayar')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('commission_amount')->label('Komisi')->money('IDR')->sortable(),
                TextColumn::make('commission_status')->label('Status')->badge(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReferralConversions::route('/'),
            'edit' => Pages\EditReferralConversion::route('/{record}/edit'),
        ];
    }
}
