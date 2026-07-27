<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReferralConversionResource\Pages;
use App\Models\ReferralConversion;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

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

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->where(function (Builder $query): void {
                $query->where('registration_commission_status', 'approved')
                    ->orWhere('herregistration_commission_status', 'approved')
                    ->orWhere('semester1_commission_status', 'approved');
            })
            ->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): string | array | null
    {
        return 'warning';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Informasi Referral')
                ->schema([
                    Placeholder::make('partner_name')
                        ->label('Partner')
                        ->content(fn (?ReferralConversion $record): string => $record?->referralPartner?->name ?? '-'),
                    Placeholder::make('partner_code')
                        ->label('Kode referral')
                        ->content(fn (?ReferralConversion $record): string => $record?->referralPartner?->referral_code ?? '-'),
                    Placeholder::make('lead_name')
                        ->label('Pendaftar')
                        ->content(fn (?ReferralConversion $record): string => $record?->lead?->full_name ?? '-'),
                    Placeholder::make('campus_name')
                        ->label('Kampus')
                        ->content(fn (?ReferralConversion $record): string => $record?->lead?->campus?->name ?? '-'),
                    Placeholder::make('study_program_name')
                        ->label('Prodi')
                        ->content(fn (?ReferralConversion $record): string => $record?->lead?->studyProgram?->name ?? '-'),
                    DateTimePicker::make('registered_at')->label('Tanggal daftar'),
                    DateTimePicker::make('paid_at')->label('Tanggal herregistrasi'),
                    DateTimePicker::make('active_at')->label('Tanggal aktif'),
                ])
                ->columns(2),
            Section::make('Rekening Affiliator')
                ->schema([
                    Placeholder::make('bank_name')
                        ->label('Bank')
                        ->content(fn (?ReferralConversion $record): string => $record?->referralPartner?->bank_name ?? '-'),
                    Placeholder::make('bank_account_number')
                        ->label('No. rekening')
                        ->content(fn (?ReferralConversion $record): string => $record?->referralPartner?->bank_account_number ?? '-'),
                    Placeholder::make('bank_account_name')
                        ->label('Atas nama')
                        ->content(fn (?ReferralConversion $record): string => $record?->referralPartner?->bank_account_name ?? '-'),
                ])
                ->columns(3),
            Section::make('Komisi Pendaftaran (50% Nominal Pendaftaran)')
                ->schema([
                    TextInput::make('registration_commission_amount')->label('Nominal')->prefix('Rp')->numeric(),
                    Select::make('registration_commission_status')
                        ->label('Status')
                        ->options(static::commissionStatusOptions())
                        ->native(false)
                        ->helperText('Otomatis diperbarui oleh sistem berdasarkan status pembayaran, namun dapat diubah manual jika diperlukan.'),
                    DateTimePicker::make('registration_paid_at')->label('Tanggal pendaftaran lunas'),
                    FileUpload::make('registration_payout_proof_path')
                        ->label('Bukti transfer')
                        ->disk('public')
                        ->directory('affiliate-payout-proofs')
                        ->fetchFileInformation(false)
                        ->openable()
                        ->downloadable(),
                    Textarea::make('registration_payout_notes')->label('Catatan payout')->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Komisi Herregistrasi')
                ->schema([
                    TextInput::make('herregistration_commission_amount')->label('Nominal')->prefix('Rp')->numeric(),
                    Select::make('herregistration_commission_status')
                        ->label('Status')
                        ->options(static::commissionStatusOptions())
                        ->native(false)
                        ->helperText('Otomatis diperbarui oleh sistem berdasarkan status pembayaran, namun dapat diubah manual jika diperlukan.'),
                    DateTimePicker::make('herregistration_paid_at')->label('Tanggal herregistrasi lunas'),
                    FileUpload::make('herregistration_payout_proof_path')
                        ->label('Bukti transfer')
                        ->disk('public')
                        ->directory('affiliate-payout-proofs')
                        ->fetchFileInformation(false)
                        ->openable()
                        ->downloadable(),
                    Textarea::make('herregistration_payout_notes')->label('Catatan payout')->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Komisi Semester 1')
                ->schema([
                    TextInput::make('semester1_commission_amount')->label('Nominal')->prefix('Rp')->numeric(),
                    Select::make('semester1_commission_status')
                        ->label('Status')
                        ->options(static::commissionStatusOptions())
                        ->native(false)
                        ->helperText('Otomatis diperbarui oleh sistem berdasarkan status pembayaran, namun dapat diubah manual jika diperlukan.'),
                    DateTimePicker::make('semester1_paid_at')->label('Tanggal semester 1 lunas'),
                    FileUpload::make('semester1_payout_proof_path')
                        ->label('Bukti transfer')
                        ->disk('public')
                        ->directory('affiliate-payout-proofs')
                        ->fetchFileInformation(false)
                        ->openable()
                        ->downloadable(),
                    Textarea::make('semester1_payout_notes')->label('Catatan payout')->columnSpanFull(),
                ])
                ->columns(2),
            Section::make('Status Keseluruhan')
                ->schema([
                    TextInput::make('commission_amount')->label('Total komisi layak')->prefix('Rp')->numeric(),
                    Select::make('commission_status')
                        ->label('Status total')
                        ->options(static::commissionStatusOptions())
                        ->disabled()
                        ->dehydrated(false)
                        ->helperText('Ditentukan otomatis oleh sistem berdasarkan status pembayaran.'),
                    Placeholder::make('payable_preview')
                        ->label('Nominal siap dibayar')
                        ->content(fn (?ReferralConversion $record): string => $record ? 'Rp '.number_format(static::payableCommission($record), 0, ',', '.') : '-'),
                ])
                ->columns(3),
        ])->columns(2);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['lead.campus', 'lead.studyProgram', 'referralPartner']);

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
                TextColumn::make('lead.full_name')->label('Pendaftar')->searchable(),
                TextColumn::make('lead.campus.name')->label('Kampus'),
                TextColumn::make('registration_commission_status')
                    ->label('Komisi Daftar')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => static::commissionStatusLabel($state))
                    ->color(fn (?string $state): string => static::commissionStatusColor($state)),
                TextColumn::make('herregistration_commission_status')
                    ->label('Komisi Herreg')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => static::commissionStatusLabel($state))
                    ->color(fn (?string $state): string => static::commissionStatusColor($state)),
                TextColumn::make('semester1_paid_at')->label('Semester 1')->dateTime('d M Y H:i')->sortable(),
                TextColumn::make('semester1_commission_status')
                    ->label('Komisi Smt 1')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => static::commissionStatusLabel($state))
                    ->color(fn (?string $state): string => static::commissionStatusColor($state)),
                TextColumn::make('payable_commission')
                    ->label('Siap Dibayar')
                    ->state(fn (ReferralConversion $record): int => static::payableCommission($record))
                    ->money('IDR')
                    ->sortable(false)
                    ->alignEnd(),
            ])
            ->filters([
                SelectFilter::make('registration_commission_status')
                    ->label('Status Komisi Pendaftaran')
                    ->options(static::commissionStatusOptions()),
                SelectFilter::make('herregistration_commission_status')
                    ->label('Status Komisi Herregistrasi')
                    ->options(static::commissionStatusOptions()),
                SelectFilter::make('semester1_commission_status')
                    ->label('Status Komisi Semester 1')
                    ->options(static::commissionStatusOptions()),
                SelectFilter::make('commission_status')
                    ->label('Status Total')
                    ->options(static::commissionStatusOptions()),
            ])
            ->actions([
                Action::make('payRegistration')
                    ->label('Bayar Layak')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        FileUpload::make('registration_payout_proof_path')
                            ->label('Bukti transfer')
                            ->disk('public')
                            ->directory('affiliate-payout-proofs')
                            ->openable()
                            ->downloadable()
                            ->required()
                            ->maxSize(4096),
                        Textarea::make('registration_payout_notes')
                            ->label('Catatan untuk affiliator')
                            ->placeholder('Contoh: Transfer komisi pendaftaran sudah diproses.'),
                    ])
                    ->modalHeading('Tandai komisi pendaftaran sudah dibayar?')
                    ->modalDescription(fn (ReferralConversion $record): string => 'Nominal: Rp '.number_format((int) $record->registration_commission_amount, 0, ',', '.'))
                    ->visible(fn (ReferralConversion $record): bool => $record->registration_commission_status === 'approved')
                    ->action(function (ReferralConversion $record, array $data): void {
                        $record->update([
                            'registration_commission_status' => 'paid',
                            'registration_payout_proof_path' => $data['registration_payout_proof_path'] ?? null,
                            'registration_payout_notes' => $data['registration_payout_notes'] ?? null,
                        ]);

                        static::refreshOverallCommissionStatus($record->refresh());
                    }),
                Action::make('payHerregistration')
                    ->label('Bayar Layak')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        FileUpload::make('herregistration_payout_proof_path')
                            ->label('Bukti transfer')
                            ->disk('public')
                            ->directory('affiliate-payout-proofs')
                            ->openable()
                            ->downloadable()
                            ->required()
                            ->maxSize(4096),
                        Textarea::make('herregistration_payout_notes')
                            ->label('Catatan untuk affiliator')
                            ->placeholder('Contoh: Transfer komisi herregistrasi sudah diproses.'),
                    ])
                    ->modalHeading('Tandai komisi herregistrasi sudah dibayar?')
                    ->modalDescription(fn (ReferralConversion $record): string => 'Nominal: Rp '.number_format((int) $record->herregistration_commission_amount, 0, ',', '.'))
                    ->visible(fn (ReferralConversion $record): bool => $record->herregistration_commission_status === 'approved')
                    ->action(function (ReferralConversion $record, array $data): void {
                        $record->update([
                            'herregistration_commission_status' => 'paid',
                            'herregistration_payout_proof_path' => $data['herregistration_payout_proof_path'] ?? null,
                            'herregistration_payout_notes' => $data['herregistration_payout_notes'] ?? null,
                        ]);

                        static::refreshOverallCommissionStatus($record->refresh());
                    }),
                Action::make('paySemester1')
                    ->label('Bayar Semester 1')
                    ->icon('heroicon-o-banknotes')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        FileUpload::make('semester1_payout_proof_path')
                            ->label('Bukti transfer')
                            ->disk('public')
                            ->directory('affiliate-payout-proofs')
                            ->openable()
                            ->downloadable()
                            ->required()
                            ->maxSize(4096),
                        Textarea::make('semester1_payout_notes')
                            ->label('Catatan untuk affiliator')
                            ->placeholder('Contoh: Transfer komisi semester 1 sudah diproses.'),
                    ])
                    ->modalHeading('Tandai komisi semester 1 sudah dibayar?')
                    ->modalDescription(fn (ReferralConversion $record): string => 'Nominal: Rp '.number_format((int) $record->semester1_commission_amount, 0, ',', '.'))
                    ->visible(fn (ReferralConversion $record): bool => $record->semester1_commission_status === 'approved')
                    ->action(function (ReferralConversion $record, array $data): void {
                        $record->update([
                            'semester1_commission_status' => 'paid',
                            'semester1_payout_proof_path' => $data['semester1_payout_proof_path'] ?? null,
                            'semester1_payout_notes' => $data['semester1_payout_notes'] ?? null,
                        ]);

                        static::refreshOverallCommissionStatus($record->refresh());
                    }),
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

    public static function commissionStatusOptions(): array
    {
        return [
            'pending' => 'Belum layak',
            'approved' => 'Layak dibayar',
            'paid' => 'Sudah dibayar',
            'cancelled' => 'Dibatalkan',
        ];
    }

    public static function commissionStatusLabel(?string $state): string
    {
        return static::commissionStatusOptions()[$state] ?? ($state ? str($state)->replace('_', ' ')->title()->toString() : '-');
    }

    public static function commissionStatusColor(?string $state): string
    {
        return match ($state) {
            'approved' => 'warning',
            'paid' => 'success',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public static function payableCommission(ReferralConversion $record): int
    {
        return (int) ($record->registration_commission_status === 'approved' ? $record->registration_commission_amount : 0)
            + (int) ($record->herregistration_commission_status === 'approved' ? $record->herregistration_commission_amount : 0)
            + (int) ($record->semester1_commission_status === 'approved' ? $record->semester1_commission_amount : 0);
    }

    public static function refreshOverallCommissionStatus(ReferralConversion $record): void
    {
        $registrationStatus = $record->registration_commission_status;
        $herStatus = $record->herregistration_commission_status;
        $semesterStatus = $record->semester1_commission_status;

        $record->update([
            'commission_status' => match (true) {
                $registrationStatus === 'paid' && $herStatus === 'paid' && $semesterStatus === 'paid' => 'paid',
                in_array($registrationStatus, ['approved', 'paid'], true) || in_array($herStatus, ['approved', 'paid'], true) || in_array($semesterStatus, ['approved', 'paid'], true) => 'approved',
                $registrationStatus === 'cancelled' && $herStatus === 'cancelled' && $semesterStatus === 'cancelled' => 'cancelled',
                default => 'pending',
            },
        ]);
    }

    public static function payoutProofUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }
}
