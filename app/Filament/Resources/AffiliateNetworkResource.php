<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AffiliateNetworkResource\Pages;
use App\Models\AffiliateNetwork;
use App\Models\ReferralPartner;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AffiliateNetworkResource extends Resource
{
    protected static ?string $model = AffiliateNetwork::class;

    protected static ?string $navigationIcon = 'heroicon-o-share';

    protected static ?string $navigationGroup = 'Afiliasi';

    protected static ?string $navigationLabel = 'Affiliate Network';

    protected static ?string $modelLabel = 'Affiliate Network';

    protected static ?int $navigationSort = 3;

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessAffiliates();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['upline', 'downline', 'createdBy']);

        if (FilamentResourceScope::isSuperAdmin() || FilamentResourceScope::isDirector()) {
            return $query;
        }

        $campusIds = auth()->user()?->campuses()->select('campuses.id');

        return $query->where(function (Builder $query) use ($campusIds): void {
            $query
                ->whereHas('upline.user.campuses', fn (Builder $campusQuery): Builder => $campusQuery->whereIn('campuses.id', $campusIds))
                ->orWhereHas('downline.user.campuses', fn (Builder $campusQuery): Builder => $campusQuery->whereIn('campuses.id', $campusIds))
                ->orWhereHas('upline.studentAccount.lead', fn (Builder $leadQuery): Builder => $leadQuery->whereIn('leads.campus_id', $campusIds))
                ->orWhereHas('downline.studentAccount.lead', fn (Builder $leadQuery): Builder => $leadQuery->whereIn('leads.campus_id', $campusIds))
                ->orWhereHas('upline.conversions.lead', fn (Builder $leadQuery): Builder => $leadQuery->whereIn('leads.campus_id', $campusIds))
                ->orWhereHas('downline.conversions.lead', fn (Builder $leadQuery): Builder => $leadQuery->whereIn('leads.campus_id', $campusIds));
        });
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Catatan')
                ->schema([
                    Placeholder::make('scope_notice')
                        ->label('Status fitur')
                        ->content('Affiliate Network masih berdiri sendiri. Data di menu ini belum terhubung ke perhitungan komisi, dashboard affiliator, atau pendaftaran mahasiswa.'),
                ])
                ->columnSpanFull(),
            TextInput::make('name')
                ->label('Nama jaringan')
                ->maxLength(255)
                ->helperText('Opsional, misalnya Batch Promo Agustus atau Network Kampus A.'),
            Select::make('upline_referral_partner_id')
                ->label('Upline / Sponsor')
                ->options(fn (): array => static::partnerOptions())
                ->searchable()
                ->preload()
                ->helperText('Kosongkan jika affiliator ini adalah root/top level.'),
            Select::make('downline_referral_partner_id')
                ->label('Downline / Affiliator')
                ->options(fn (): array => static::partnerOptions())
                ->searchable()
                ->preload()
                ->required()
                ->different('upline_referral_partner_id'),
            TextInput::make('level')
                ->label('Level')
                ->numeric()
                ->minValue(1)
                ->maxValue(20)
                ->default(1)
                ->required(),
            Select::make('position')
                ->label('Posisi')
                ->options([
                    'left' => 'Kiri',
                    'center' => 'Tengah',
                    'right' => 'Kanan',
                    'free' => 'Bebas',
                ])
                ->placeholder('Tidak ditentukan'),
            Select::make('status')
                ->label('Status')
                ->options([
                    'draft' => 'Draft',
                    'active' => 'Aktif',
                    'inactive' => 'Nonaktif',
                ])
                ->default('draft')
                ->required(),
            Textarea::make('notes')
                ->label('Catatan')
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('name')->label('Network')->searchable()->toggleable(),
                TextColumn::make('upline.name')->label('Upline')->searchable()->placeholder('Root'),
                TextColumn::make('upline.referral_code')->label('Kode Upline')->copyable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('downline.name')->label('Downline')->searchable(),
                TextColumn::make('downline.referral_code')->label('Kode Downline')->copyable(),
                TextColumn::make('level')->label('Level')->sortable()->alignCenter(),
                TextColumn::make('position')
                    ->label('Posisi')
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'left' => 'Kiri',
                        'center' => 'Tengah',
                        'right' => 'Kanan',
                        'free' => 'Bebas',
                        default => '-',
                    }),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                        default => 'Draft',
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'inactive' => 'gray',
                        default => 'warning',
                    }),
                TextColumn::make('createdBy.name')->label('Dibuat oleh')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')->label('Dibuat')->dateTime('d M Y H:i')->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Aktif',
                        'inactive' => 'Nonaktif',
                    ]),
                SelectFilter::make('level')
                    ->label('Level')
                    ->options(collect(range(1, 10))->mapWithKeys(fn (int $level): array => [$level => 'Level '.$level])->all()),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => ! FilamentResourceScope::isDirector()),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn (): bool => ! FilamentResourceScope::isDirector()),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->visible(fn (): bool => ! FilamentResourceScope::isDirector()),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return ! FilamentResourceScope::isDirector() && parent::canCreate();
    }

    public static function canEdit(Model $record): bool
    {
        return ! FilamentResourceScope::isDirector() && parent::canEdit($record);
    }

    public static function canDelete(Model $record): bool
    {
        return ! FilamentResourceScope::isDirector() && parent::canDelete($record);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAffiliateNetworks::route('/'),
            'visual' => Pages\VisualAffiliateNetwork::route('/visual'),
            'create' => Pages\CreateAffiliateNetwork::route('/create'),
            'edit' => Pages\EditAffiliateNetwork::route('/{record}/edit'),
        ];
    }

    private static function partnerOptions(): array
    {
        return ReferralPartner::query()
            ->when(! (FilamentResourceScope::isSuperAdmin() || FilamentResourceScope::isDirector()), function (Builder $query): Builder {
                $campusIds = auth()->user()?->campuses()->select('campuses.id');

                return $query->where(function (Builder $query) use ($campusIds): void {
                    $query
                        ->whereHas('user.campuses', fn (Builder $campusQuery): Builder => $campusQuery->whereIn('campuses.id', $campusIds))
                        ->orWhereHas('studentAccount.lead', fn (Builder $leadQuery): Builder => $leadQuery->whereIn('leads.campus_id', $campusIds))
                        ->orWhereHas('conversions.lead', fn (Builder $leadQuery): Builder => $leadQuery->whereIn('leads.campus_id', $campusIds));
                });
            })
            ->orderBy('name')
            ->get(['id', 'name', 'referral_code'])
            ->mapWithKeys(fn (ReferralPartner $partner): array => [$partner->id => $partner->name.' - '.$partner->referral_code])
            ->all();
    }
}