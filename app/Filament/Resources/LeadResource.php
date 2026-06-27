<?php

namespace App\Filament\Resources;

use App\Enums\LeadStatus;
use App\Enums\ProspectStatus;
use App\Filament\Resources\LeadResource\Pages;
use App\Models\Campus;
use App\Models\ClassTrack;
use App\Models\Lead;
use App\Models\StudyProgram;
use App\Models\User;
use App\Services\InvoiceRegenerationService;
use App\Services\LeadAssignmentService;
use App\Services\LeadProspectStatusService;
use App\Support\FilamentResourceScope;
use DomainException;
use Filament\Notifications\Notification;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LeadResource extends Resource
{
    protected static ?string $model = Lead::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static ?string $navigationGroup = 'CRM';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Data PMB')
                ->columns(3)
                ->schema([
                    Select::make('campus_id')
                        ->label('Kampus')
                        ->options(fn (): array => static::campusOptions())
                        ->searchable()
                        ->preload()
                        ->live()
                        ->visible(fn (): bool => ! FilamentResourceScope::isStaff())
                        ->disabled(fn (): bool => FilamentResourceScope::isStaff())
                        ->dehydrated()
                        ->afterStateUpdated(function (Set $set): void {
                            $set('study_program_id', null);
                            $set('class_track_id', null);
                        })
                        ->required(),
                    TextInput::make('campus_name_display')
                        ->label('Kampus')
                        ->formatStateUsing(fn (?Lead $record): string => $record?->campus?->name ?? '-')
                        ->visible(fn (): bool => FilamentResourceScope::isStaff())
                        ->disabled()
                        ->dehydrated(false),
                    Hidden::make('campus_id')
                        ->visible(fn (): bool => FilamentResourceScope::isStaff())
                        ->dehydrated(),
                    Select::make('study_program_id')
                        ->label('Program studi')
                        ->options(fn (Get $get): array => filled($get('campus_id'))
                            ? StudyProgram::query()
                                ->where('campus_id', $get('campus_id'))
                                ->orderBy('degree_level')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (StudyProgram $program): array => [
                                    $program->id => trim(($program->degree_level ? $program->degree_level.' - ' : '').$program->name),
                                ])
                                ->all()
                            : [])
                        ->searchable()
                        ->preload()
                        ->disabled(fn (Get $get): bool => blank($get('campus_id')))
                        ->required(),
                    Select::make('class_track_id')
                        ->label('Program perkuliahan')
                        ->options(fn (Get $get): array => filled($get('campus_id'))
                            ? ClassTrack::query()
                                ->where('campus_id', $get('campus_id'))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()
                            : [])
                        ->searchable()
                        ->preload()
                        ->disabled(fn (Get $get): bool => blank($get('campus_id')))
                        ->required(),
                    Select::make('assigned_to_user_id')
                        ->label('Staff PMB')
                        ->options(fn (): array => static::staffOptions())
                        ->searchable()
                        ->preload()
                        ->disabled(fn (): bool => FilamentResourceScope::isStaff())
                        ->dehydrated(),
                    TextInput::make('full_name')->label('Nama')->required()->maxLength(255),
                    TextInput::make('whatsapp_number')->label('No. WA')->required()->maxLength(32),
                    TextInput::make('email')->label('Email')->email()->required()->maxLength(255),
                    TextInput::make('origin_school')->label('Asal sekolah')->maxLength(255),
                    TextInput::make('graduation_year')->label('Tahun lulus')->numeric(),
                    Select::make('lead_status')
                        ->label('Status lead')
                        ->options(static::leadStatusOptions())
                        ->required(),
                    Select::make('prospect_status')
                        ->label('Status prospek')
                        ->options(static::prospectStatusOptions())
                        ->native(false),
                    TextInput::make('payment_status')->label('Status pembayaran')->disabled(),
                    TextInput::make('enrollment_status')->label('Status enrollment')->disabled(),
                    DateTimePicker::make('locked_at')->label('Dikunci pada')->disabled(),
                    Hidden::make('source_channel')
                        ->default('website'),
                    TextInput::make('source_channel_display')
                        ->label('Sumber lead')
                        ->formatStateUsing(fn (?Lead $record): string => static::sourceLabel($record?->source_channel ?? 'website'))
                        ->default('Website')
                        ->disabled()
                        ->dehydrated(false),
                    Textarea::make('source_detail')
                        ->label('Detail sumber lead')
                        ->default('Website')
                        ->columnSpanFull(),
                ]),
            Section::make('1. Data Pendaftaran Mahasiswa')
                ->relationship('studentBiodata')
                ->columns(2)
                ->schema([
                    Hidden::make('student_type')->default('new'),
                    Hidden::make('campus_id')->default(fn (?Lead $record): ?int => $record?->campus_id),
                    Hidden::make('study_program_id')->default(fn (?Lead $record): ?int => $record?->study_program_id),
                    Hidden::make('class_track_id')->default(fn (?Lead $record): ?int => $record?->class_track_id),
                    TextInput::make('name')->label('Nama')->maxLength(255),
                    TextInput::make('selection_number')->label('No. Seleksi')->disabled()->dehydrated(false),
                    TextInput::make('student_number')->label('NIM')->maxLength(64),
                    Select::make('group')
                        ->label('Kelompok')
                        ->options([
                            'D3 ke S1' => 'D3 ke S1',
                            'SMU/Sederajat ke S1' => 'SMU/Sederajat ke S1',
                        ]),
                    TextInput::make('financial_status')->label('Status Keuangan')->disabled()->dehydrated(false),
                    Select::make('information_source')
                        ->label('Sumber Informasi')
                        ->options(StudentBiodataResourceSchema::informationSourceOptions())
                        ->searchable()
                        ->live(),
                    TextInput::make('affiliator_code')
                        ->label('Kode Affiliator')
                        ->maxLength(64)
                        ->visible(fn (Get $get): bool => $get('information_source') === 'Affiliator'),
                    TextInput::make('information_source_other')
                        ->label('Sumber Informasi Lainnya')
                        ->maxLength(255)
                        ->visible(fn (Get $get): bool => $get('information_source') === 'Lainnya'),
                    FileUpload::make('photo_path')
                        ->label('Foto')
                        ->image()
                        ->directory('student-photos')
                        ->imageEditor()
                        ->columnSpanFull(),
                ]),
            Section::make('2. Biodata Mahasiswa')
                ->relationship('studentBiodata')
                ->columns(2)
                ->schema([
                    TextInput::make('birth_place')->label('Tempat Lahir')->maxLength(255),
                    DatePicker::make('birth_date')->label('Tanggal Lahir'),
                    Select::make('religion')
                        ->label('Agama')
                        ->options([
                            'Islam' => 'Islam',
                            'Kristen' => 'Kristen',
                            'Katolik' => 'Katolik',
                            'Hindu' => 'Hindu',
                            'Buddha' => 'Buddha',
                            'Konghucu' => 'Konghucu',
                        ]),
                    Select::make('gender')
                        ->label('Gender')
                        ->options([
                            'Laki-laki' => 'Laki-laki',
                            'Perempuan' => 'Perempuan',
                        ]),
                    Textarea::make('address')->label('Alamat KTP')->rows(3)->columnSpanFull(),
                    Textarea::make('mailing_address')->label('Alamat Domisili saat ini')->rows(3)->columnSpanFull(),
                    TextInput::make('city')->label('Kota/Kabupaten')->maxLength(255),
                    TextInput::make('district')->label('Kecamatan')->maxLength(255),
                    TextInput::make('village')->label('Kelurahan')->maxLength(255),
                    TextInput::make('postal_code')->label('Kode Pos')->maxLength(16),
                    TextInput::make('rt_rw')->label('RT/RW')->maxLength(32),
                    TextInput::make('phone')->label('No. Telepon')->maxLength(32),
                    TextInput::make('mobile_phone')->label('No. Handphone')->maxLength(32),
                    TextInput::make('email')->label('Email')->email()->maxLength(255),
                    TextInput::make('whatsapp_number')->label('No. WA')->maxLength(32),
                    TextInput::make('identity_number')->label('NIK KTP Mahasiswa')->maxLength(32),
                ]),
            Section::make('3. Data Keluarga')
                ->relationship('studentBiodata')
                ->columns(2)
                ->schema([
                    TextInput::make('mother_name')->label('Nama Ibu Kandung')->maxLength(255),
                    TextInput::make('family_name')->label('Nama kontak keluarga')->maxLength(255),
                    TextInput::make('family_number')->label('No HP/WA kontak keluarga')->maxLength(64),
                    TextInput::make('family_relationship')->label('Hubungan')->maxLength(255),
                ]),
            Section::make('4. Data Pekerjaan')
                ->relationship('studentBiodata')
                ->columns(2)
                ->schema([
                    TextInput::make('company_name')->label('Perusahaan/Instansi')->maxLength(255),
                    TextInput::make('job_title')->label('Jabatan')->maxLength(255),
                    Textarea::make('company_address')->label('Alamat Perusahaan')->rows(3)->columnSpanFull(),
                    TextInput::make('company_city')->label('Kota/Kabupaten')->maxLength(255),
                    TextInput::make('company_postal_code')->label('Kode Pos')->maxLength(16),
                    TextInput::make('company_phone')->label('No. Telepon')->maxLength(32),
                ]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return FilamentResourceScope::applyLeadScope(parent::getEloquentQuery());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_name')->searchable()->sortable(),
                TextColumn::make('email')->searchable()->toggleable(),
                TextColumn::make('campus.name')->sortable(),
                TextColumn::make('studyProgram.name')->toggleable(),
                TextColumn::make('assignedTo.name')->placeholder('Pool'),
                TextColumn::make('lead_status')
                    ->label('Status lead')
                    ->formatStateUsing(fn ($state): string => static::leadStatusLabel($state?->value ?? $state))
                    ->badge(),
                TextColumn::make('prospect_status')
                    ->label('Prospek')
                    ->formatStateUsing(fn ($state): string => static::prospectStatusLabel($state?->value ?? $state))
                    ->color(fn ($state): string => static::prospectStatusColor($state?->value ?? $state))
                    ->badge()
                    ->placeholder('-'),
                TextColumn::make('source_channel')
                    ->label('Sumber lead')
                    ->formatStateUsing(fn (?string $state): string => static::sourceLabel($state))
                    ->badge()
                    ->toggleable(),
                TextColumn::make('payment_status')->badge(),
                TextColumn::make('enrollment_status')->badge(),
                TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('campus_id')
                    ->label('Kampus')
                    ->options(fn (): array => static::campusOptions()),
                Tables\Filters\SelectFilter::make('lead_status')->options(
                    static::leadStatusOptions()
                ),
                Tables\Filters\SelectFilter::make('prospect_status')
                    ->label('Status prospek')
                    ->options(static::prospectStatusOptions()),
            ])
            ->actions([
                Tables\Actions\Action::make('assign')
                    ->label('Bagikan')
                    ->icon('heroicon-o-user-plus')
                    ->modalHeading('Bagikan lead ke Staff PMB')
                    ->modalSubmitActionLabel('Bagikan')
                    ->modalCancelActionLabel('Batal')
                    ->modalWidth('md')
                    ->visible(fn (): bool => ! FilamentResourceScope::isStaff())
                    ->form([
                        Select::make('staff_id')
                            ->label('Staff PMB')
                            ->options(fn (): array => static::staffOptions())
                            ->native()
                            ->required(),
                    ])
                    ->action(function (Lead $record, array $data, LeadAssignmentService $assignments): void {
                        $staff = User::query()->findOrFail($data['staff_id']);
                        $assignments->assign([$record->id], $staff, auth()->user());

                        Notification::make()->title('Lead berhasil dibagikan')->success()->send();
                    }),
                Tables\Actions\Action::make('regenerate_invoice')
                    ->label('Buat Ulang Tagihan')
                    ->icon('heroicon-o-arrow-path')
                    ->requiresConfirmation()
                    ->action(function (Lead $record, InvoiceRegenerationService $regeneration): void {
                        try {
                            $regeneration->regenerate($record);
                            Notification::make()->title('Invoice baru dibuat')->success()->send();
                    } catch (DomainException $exception) {
                            Notification::make()->title($exception->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('follow_up_whatsapp')
                    ->label('Follow Up WA')
                    ->icon('heroicon-o-phone')
                    ->url(fn (Lead $record): ?string => static::whatsappFollowUpUrl($record))
                    ->openUrlInNewTab()
                    ->visible(fn (Lead $record): bool => filled(static::normalizeWhatsappForUrl($record->whatsapp_number))),
                Tables\Actions\ActionGroup::make(
                    collect(ProspectStatus::cases())
                        ->map(fn (ProspectStatus $status): Tables\Actions\Action => Tables\Actions\Action::make('prospect_'.$status->value)
                            ->label(static::prospectStatusLabel($status->value))
                            ->icon(static::prospectStatusIcon($status->value))
                            ->color(static::prospectStatusColor($status->value))
                            ->action(function (Lead $record, LeadProspectStatusService $service) use ($status): void {
                                $event = $service->update($record, $status->value, auth()->user());

                                Notification::make()
                                    ->title('Status prospek diperbarui')
                                    ->body('Meta event: '.$event->meta_status)
                                    ->success()
                                    ->send();
                            }))
                        ->all()
                )
                    ->label('Update Prospek')
                    ->icon('heroicon-o-signal'),
                Tables\Actions\EditAction::make()
                    ->label('Ubah'),
                Tables\Actions\DeleteAction::make()
                    ->label('Hapus')
                    ->modalHeading('Hapus lead')
                    ->modalDescription('Data lead dan data terkait seperti invoice, biodata, dokumen, pembayaran, dan aktivitas akan ikut dihapus.')
                    ->modalSubmitActionLabel('Ya, hapus')
                    ->modalCancelActionLabel('Batal')
                    ->visible(fn (): bool => ! FilamentResourceScope::isStaff()),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('assign_selected')
                    ->label('Bagikan Terpilih')
                    ->icon('heroicon-o-user-plus')
                    ->modalHeading('Bagikan lead terpilih')
                    ->modalSubmitActionLabel('Bagikan')
                    ->modalCancelActionLabel('Batal')
                    ->modalWidth('md')
                    ->visible(fn (): bool => ! FilamentResourceScope::isStaff())
                    ->form([
                        Select::make('staff_id')
                            ->label('Staff PMB')
                            ->options(fn (): array => static::staffOptions())
                            ->native()
                            ->required(),
                    ])
                    ->action(function ($records, array $data, LeadAssignmentService $assignments): void {
                        $staff = User::query()->findOrFail($data['staff_id']);
                        $assignments->assign($records->pluck('id')->all(), $staff, auth()->user());

                        Notification::make()
                            ->title('Lead terpilih berhasil dibagikan')
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Hapus Terpilih')
                    ->modalHeading('Hapus lead terpilih')
                    ->modalDescription('Semua lead yang dicentang beserta data terkaitnya akan dihapus.')
                    ->modalSubmitActionLabel('Ya, hapus')
                    ->modalCancelActionLabel('Batal')
                    ->visible(fn (): bool => ! FilamentResourceScope::isStaff()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeads::route('/'),
            'edit' => Pages\EditLead::route('/{record}/edit'),
        ];
    }

    protected static function campusOptions(): array
    {
        $query = Campus::query()->orderBy('name');

        if (FilamentResourceScope::isCoordinator()) {
            $query->whereIn('id', auth()->user()?->campuses()->select('campuses.id') ?? []);
        }

        if (FilamentResourceScope::isStaff()) {
            $query->whereIn('id', auth()->user()?->campuses()->select('campuses.id') ?? []);
        }

        return $query->pluck('name', 'id')->all();
    }

    protected static function leadStatusOptions(): array
    {
        return collect(LeadStatus::cases())
            ->mapWithKeys(fn (LeadStatus $status): array => [$status->value => static::leadStatusLabel($status->value)])
            ->all();
    }

    public static function prospectStatusOptions(): array
    {
        return collect(ProspectStatus::cases())
            ->mapWithKeys(fn (ProspectStatus $status): array => [$status->value => static::prospectStatusLabel($status->value)])
            ->all();
    }

    protected static function leadStatusLabel(?string $status): string
    {
        return match ($status) {
            LeadStatus::New->value => 'Baru',
            LeadStatus::InPool->value => 'Masuk Pool',
            LeadStatus::Assigned->value => 'Sudah Dibagikan',
            LeadStatus::Contacted->value => 'Sudah Dihubungi',
            LeadStatus::Interested->value => 'Berminat',
            LeadStatus::NotQualified->value => 'Tidak Memenuhi Syarat',
            LeadStatus::Lost->value => 'Tidak Lanjut',
            LeadStatus::Converted->value => 'Menjadi Mahasiswa',
            default => str((string) $status)->replace('_', ' ')->title()->toString(),
        };
    }

    protected static function sourceLabel(?string $source): string
    {
        return match ($source) {
            'website', 'public_form' => 'Website',
            'partner_website' => 'Web Kampus Mitra',
            'meta', 'meta_ads' => 'Iklan Meta',
            'google', 'google_ads' => 'Iklan Google',
            'manual' => 'Manual',
            'referral', 'affiliate', 'affiliator' => 'Affiliator',
            default => filled($source) ? str($source)->replace('_', ' ')->title()->toString() : 'Website',
        };
    }

    public static function prospectStatusLabel(?string $status): string
    {
        return match ($status) {
            ProspectStatus::Cold->value => 'Cold',
            ProspectStatus::Warm->value => 'Warm',
            ProspectStatus::Hot->value => 'Hot',
            ProspectStatus::Closing->value => 'Closing',
            default => filled($status) ? str($status)->replace('_', ' ')->title()->toString() : '-',
        };
    }

    public static function prospectStatusColor(?string $status): string
    {
        return match ($status) {
            ProspectStatus::Cold->value => 'gray',
            ProspectStatus::Warm->value => 'warning',
            ProspectStatus::Hot->value => 'danger',
            ProspectStatus::Closing->value => 'success',
            default => 'gray',
        };
    }

    public static function prospectStatusIcon(?string $status): string
    {
        return match ($status) {
            ProspectStatus::Cold->value => 'heroicon-o-minus-circle',
            ProspectStatus::Warm->value => 'heroicon-o-sparkles',
            ProspectStatus::Hot->value => 'heroicon-o-fire',
            ProspectStatus::Closing->value => 'heroicon-o-check-circle',
            default => 'heroicon-o-signal',
        };
    }

    protected static function staffOptions(): array
    {
        $query = User::query()
            ->where('role', 'staff_pmb')
            ->orderBy('name');

        if (FilamentResourceScope::isCoordinator()) {
            $query->whereHas('campuses', fn (Builder $campusQuery): Builder => $campusQuery->whereIn('campuses.id', auth()->user()?->campuses()->select('campuses.id') ?? []));
        }

        return $query->pluck('name', 'id')->all();
    }

    public static function whatsappFollowUpUrl(Lead $lead): ?string
    {
        $number = static::normalizeWhatsappForUrl($lead->whatsapp_number);

        if (blank($number)) {
            return null;
        }

        $staffName = auth()->user()?->name ?: 'Tim PMB';
        $campusName = $lead->campus?->name ?: 'kampus pilihan Anda';
        $programName = trim(($lead->studyProgram?->degree_level ? $lead->studyProgram->degree_level.' ' : '').($lead->studyProgram?->name ?? ''));
        $programText = filled($programName) ? ' program '.$programName : '';

        $message = "Halo {$lead->full_name}, saya {$staffName} dari Kampus Media. Saya ingin follow up pendaftaran Anda untuk {$campusName}{$programText}. Apakah masih berminat melanjutkan proses pendaftaran?";

        return 'https://wa.me/'.$number.'?text='.rawurlencode($message);
    }

    protected static function normalizeWhatsappForUrl(?string $number): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $number);

        if ($digits === '') {
            return null;
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }
}
