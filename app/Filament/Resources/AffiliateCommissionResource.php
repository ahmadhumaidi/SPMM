<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AffiliateCommissionResource\Pages;
use App\Models\AffiliateCommission;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AffiliateCommissionResource extends Resource
{
    protected static ?string $model = AffiliateCommission::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Afiliasi';

    protected static ?string $navigationLabel = 'Komisi Affiliate';

    protected static ?string $modelLabel = 'Komisi Affiliate';

    protected static ?int $navigationSort = 4;

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessAffiliates();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with([
            'referralPartner',
            'lead.campus',
            'lead.studyProgram',
            'studentPayment',
        ]);

        if (FilamentResourceScope::isSuperAdmin() || FilamentResourceScope::isDirector()) {
            return $query;
        }

        $campusIds = auth()->user()?->campuses()->select('campuses.id');

        return $query->whereHas('lead', fn (Builder $leadQuery): Builder => $leadQuery->whereIn('campus_id', $campusIds));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Komisi')
                ->schema([
                    TextInput::make('referralPartner.name')->label('Affiliator')->disabled(),
                    TextInput::make('lead.full_name')->label('Mahasiswa')->disabled(),
                    TextInput::make('stage')->label('Milestone')->disabled(),
                    TextInput::make('commission_level')->label('Level')->disabled(),
                    TextInput::make('amount')->label('Nominal')->numeric()->disabled(),
                    TextInput::make('status')->label('Status')->disabled(),
                    TextInput::make('approved_at')->label('Disetujui')->disabled(),
                    TextInput::make('paid_at')->label('Dibayar')->disabled(),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('referralPartner.name')->label('Affiliator')->searchable()->sortable(),
                TextColumn::make('referralPartner.referral_code')->label('Kode')->copyable()->searchable(),
                TextColumn::make('lead.full_name')->label('Mahasiswa')->searchable()->sortable(),
                TextColumn::make('lead.campus.name')->label('Kampus')->searchable()->toggleable(),
                TextColumn::make('stage')
                    ->label('Milestone')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        AffiliateCommission::STAGE_REGISTRATION => 'Registrasi',
                        AffiliateCommission::STAGE_HERREGISTRATION => 'Herregistrasi',
                        AffiliateCommission::STAGE_SEMESTER_1_PAID => 'Semester 1 Lunas',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        AffiliateCommission::STAGE_REGISTRATION => 'info',
                        AffiliateCommission::STAGE_HERREGISTRATION => 'warning',
                        AffiliateCommission::STAGE_SEMESTER_1_PAID => 'success',
                        default => 'gray',
                    }),
                TextColumn::make('commission_level')
                    ->label('Penerima')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        AffiliateCommission::LEVEL_DIRECT => 'Direct',
                        AffiliateCommission::LEVEL_UPLINE_1 => 'Upline 1',
                        AffiliateCommission::LEVEL_UPLINE_2 => 'Upline 2',
                        AffiliateCommission::LEVEL_UPLINE_3 => 'Upline 3',
                        default => $state,
                    }),
                TextColumn::make('amount')->label('Nominal')->money('IDR')->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        AffiliateCommission::STATUS_APPROVED => 'Layak dibayar',
                        AffiliateCommission::STATUS_PAID => 'Terbayar',
                        AffiliateCommission::STATUS_CANCELLED => 'Dibatalkan',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        AffiliateCommission::STATUS_APPROVED => 'warning',
                        AffiliateCommission::STATUS_PAID => 'success',
                        AffiliateCommission::STATUS_CANCELLED => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('approved_at')->label('Tanggal layak')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('paid_at')->label('Tanggal bayar')->dateTime('d M Y H:i')->placeholder('-')->sortable(),
            ])
            ->filters([
                SelectFilter::make('stage')
                    ->label('Milestone')
                    ->options([
                        AffiliateCommission::STAGE_REGISTRATION => 'Registrasi',
                        AffiliateCommission::STAGE_HERREGISTRATION => 'Herregistrasi',
                        AffiliateCommission::STAGE_SEMESTER_1_PAID => 'Semester 1 Lunas',
                    ]),
                SelectFilter::make('commission_level')
                    ->label('Penerima')
                    ->options([
                        AffiliateCommission::LEVEL_DIRECT => 'Direct',
                        AffiliateCommission::LEVEL_UPLINE_1 => 'Upline 1',
                        AffiliateCommission::LEVEL_UPLINE_2 => 'Upline 2',
                        AffiliateCommission::LEVEL_UPLINE_3 => 'Upline 3',
                    ]),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        AffiliateCommission::STATUS_APPROVED => 'Layak dibayar',
                        AffiliateCommission::STATUS_PAID => 'Terbayar',
                        AffiliateCommission::STATUS_CANCELLED => 'Dibatalkan',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAffiliateCommissions::route('/'),
            'view' => Pages\ViewAffiliateCommission::route('/{record}'),
        ];
    }
}
