<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralPartnerResource\Pages;
use App\Models\ReferralPartner;
use App\Models\StudentAccount;
use App\Models\User;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ReferralPartnerResource extends Resource
{
    protected static ?string $model = ReferralPartner::class;

    protected static ?string $navigationIcon = 'heroicon-o-link';

    protected static ?string $navigationGroup = 'Afiliasi';

    protected static ?string $navigationLabel = 'Affiliator';

    protected static ?string $modelLabel = 'Affiliator';

    protected static ?int $navigationSort = 1;

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessAffiliates();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['user.campuses', 'studentAccount.lead.campus']);

        if (FilamentResourceScope::isSuperAdmin() || FilamentResourceScope::isDirector()) {
            return $query;
        }

        $campusIds = auth()->user()?->campuses()->select('campuses.id');

        return $query->where(function (Builder $query) use ($campusIds): void {
            $query
                ->whereHas('user.campuses', fn (Builder $campusQuery): Builder => $campusQuery->whereIn('campuses.id', $campusIds))
                ->orWhereHas('studentAccount.lead', fn (Builder $leadQuery): Builder => $leadQuery->whereIn('leads.campus_id', $campusIds))
                ->orWhereHas('conversions.lead', fn (Builder $leadQuery): Builder => $leadQuery->whereIn('leads.campus_id', $campusIds));
        });
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()->where('created_at', '>=', now()->subDay())->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string | array | null
    {
        return 'success';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label('Nama partner')
                ->required()
                ->maxLength(255),
            Select::make('type')
                ->label('Tipe partner')
                ->options([
                    'staff' => 'Staff',
                    'mahasiswa' => 'Mahasiswa',
                    'umum' => 'Umum',
                ])
                ->default('staff')
                ->required()
                ->live(),
            Select::make('user_id')
                ->label('User staff')
                ->options(fn (): array => User::query()
                    ->when(! FilamentResourceScope::isSuperAdmin(), fn (Builder $query): Builder => $query->whereHas('campuses', fn (Builder $campusQuery): Builder => FilamentResourceScope::applyCampusScope($campusQuery, 'campuses.id')))
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->all())
                ->searchable()
                ->visible(fn ($get): bool => $get('type') === 'staff'),
            Select::make('student_account_id')
                ->label('Akun mahasiswa')
                ->options(fn (): array => StudentAccount::query()
                    ->with('lead')
                    ->when(! FilamentResourceScope::isSuperAdmin(), fn (Builder $query): Builder => $query->whereHas('lead', fn (Builder $leadQuery): Builder => FilamentResourceScope::applyManagedLeadCampusScope($leadQuery)))
                    ->get()
                    ->mapWithKeys(fn (StudentAccount $account): array => [$account->id => $account->lead?->full_name.' - '.$account->email])
                    ->all())
                ->searchable()
                ->visible(fn ($get): bool => $get('type') === 'mahasiswa'),
            TextInput::make('email')
                ->label('Email')
                ->email()
                ->maxLength(255)
                ->visible(fn ($get): bool => $get('type') === 'umum'),
            TextInput::make('password')
                ->label('Password dashboard')
                ->password()
                ->revealable()
                ->minLength(8)
                ->dehydrateStateUsing(fn (?string $state): ?string => filled($state) ? Hash::make($state) : null)
                ->dehydrated(fn (?string $state): bool => filled($state))
                ->visible(fn ($get): bool => $get('type') === 'umum')
                ->helperText('Isi hanya jika ingin membuat atau mengganti password affiliator umum.'),
            TextInput::make('phone')
                ->label('No HP/WA')
                ->maxLength(32)
                ->visible(fn ($get): bool => $get('type') === 'umum'),
            Textarea::make('address')
                ->label('Alamat KTP')
                ->visible(fn ($get): bool => $get('type') === 'umum')
                ->columnSpanFull(),
            TextInput::make('source_information')
                ->label('Sumber informasi')
                ->maxLength(255)
                ->visible(fn ($get): bool => $get('type') === 'umum'),
            TextInput::make('npwp')
                ->label('NPWP')
                ->maxLength(32)
                ->visible(fn ($get): bool => $get('type') === 'umum')
                ->helperText('Pajak komisi ditanggung oleh penerima komisi.'),
            TextInput::make('bank_name')
                ->label('Bank')
                ->maxLength(100)
                ->visible(fn ($get): bool => $get('type') === 'umum'),
            TextInput::make('bank_account_number')
                ->label('Nomor rekening')
                ->maxLength(64)
                ->visible(fn ($get): bool => $get('type') === 'umum'),
            TextInput::make('bank_account_name')
                ->label('Atas nama rekening')
                ->maxLength(255)
                ->visible(fn ($get): bool => $get('type') === 'umum'),
            FileUpload::make('identity_card_path')
                ->label('KTP')
                ->disk('public')
                ->directory('affiliate-identity-cards')
                ->fetchFileInformation(false)
                ->openable()
                ->downloadable()
                ->visible(fn ($get): bool => $get('type') === 'umum'),
            FileUpload::make('digital_signature_path')
                ->label('Tanda tangan digital')
                ->disk('public')
                ->directory('affiliate-signatures')
                ->image()
                ->fetchFileInformation(false)
                ->openable()
                ->downloadable()
                ->visible(fn ($get): bool => $get('type') === 'umum'),
            TextInput::make('terms_document_path')
                ->label('Dokumen syarat dan ketentuan')
                ->disabled()
                ->dehydrated(false)
                ->visible(fn ($get): bool => $get('type') === 'umum'),
            TextInput::make('referral_code')
                ->label('Kode referral')
                ->required()
                ->unique(ignoreRecord: true)
                ->maxLength(64)
                ->default(fn (): string => 'REF-'.Str::upper(Str::random(8)))
                ->helperText('Link: /daftar?ref=KODE'),
            TextInput::make('dashboard_token')
                ->label('Token dashboard')
                ->default(fn (): string => Str::random(64))
                ->maxLength(80)
                ->disabled()
                ->dehydrated(false)
                ->visible(fn ($get): bool => $get('type') === 'umum')
                ->helperText('Link dashboard affiliator dibuat otomatis dari token ini. Gunakan aksi "Regenerasi token" di tabel untuk mengganti.'),
            TextInput::make('commission_amount')
                ->label('Komisi')
                ->prefix('Rp')
                ->numeric()
                ->default(0),
            Select::make('status')
                ->label('Status')
                ->options([
                    'active' => 'Aktif',
                    'inactive' => 'Nonaktif',
                ])
                ->default('active')
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
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('type')->label('Tipe')->badge(),
                TextColumn::make('email')->label('Email')->searchable()->toggleable(),
                TextColumn::make('phone')->label('No HP/WA')->searchable()->toggleable(),
                TextColumn::make('npwp')->label('NPWP')->searchable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('referral_code')->label('Kode')->copyable()->searchable(),
                TextColumn::make('referral_link')
                    ->label('Link')
                    ->state(fn (ReferralPartner $record): string => rtrim(config('spmm.public_url', 'https://kampus.media'), '/').'/daftar?ref='.$record->referral_code)
                    ->copyable()
                    ->limit(44)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('conversions_count')->counts('conversions')->label('Pendaftar'),
                TextColumn::make('commission_amount')->label('Komisi')->money('IDR')->sortable()->alignEnd(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('email_verified_at')
                    ->label('Verifikasi Email')
                    ->state(fn (ReferralPartner $record): string => $record->email_verified_at ? 'Terverifikasi' : 'Belum')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Terverifikasi' ? 'success' : 'warning'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->visible(fn (): bool => ! FilamentResourceScope::isDirector()),
                Tables\Actions\Action::make('open_dashboard')
                    ->label('Dashboard')
                    ->icon('heroicon-o-arrow-top-right-on-square')
                    ->url(fn (ReferralPartner $record): ?string => $record->dashboard_token ? 'https://affiliate.kampus.media/dashboard/'.$record->dashboard_token : null)
                    ->openUrlInNewTab()
                    ->visible(fn (ReferralPartner $record): bool => $record->type === 'umum' && filled($record->dashboard_token)),
                Tables\Actions\Action::make('regenerate_dashboard_token')
                    ->label('Regenerasi token')
                    ->icon('heroicon-o-arrow-path')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Link dashboard yang lama akan berhenti berfungsi. Bagikan link baru ke affiliator.')
                    ->visible(fn (ReferralPartner $record): bool => $record->type === 'umum' && ! FilamentResourceScope::isDirector())
                    ->action(fn (ReferralPartner $record) => $record->update(['dashboard_token' => Str::random(64)])),
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

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return ! FilamentResourceScope::isDirector() && parent::canEdit($record);
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return ! FilamentResourceScope::isDirector() && parent::canDelete($record);
    }
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReferralPartners::route('/'),
            'create' => Pages\CreateReferralPartner::route('/create'),
            'edit' => Pages\EditReferralPartner::route('/{record}/edit'),
        ];
    }
}


