<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FeeSchemeResource\Pages;
use App\Models\ClassTrack;
use App\Models\FeeScheme;
use App\Models\StudyProgram;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class FeeSchemeResource extends Resource
{
    protected static ?string $model = FeeScheme::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Master Data';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessMasterData();
    }

    protected static ?int $navigationSort = 4;

    public static function isSuperAdmin(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function studyProgramOptionsForCampus(mixed $campusId): array
    {
        if (blank($campusId)) {
            return [];
        }

        return StudyProgram::query()
            ->where('campus_id', $campusId)
            ->orderBy('degree_level')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (StudyProgram $program): array => [
                $program->id => trim(($program->degree_level ? $program->degree_level.' ' : '').$program->name),
            ])
            ->all();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Kampus & Program')
                ->columns(2)
                ->schema([
                    Select::make('campus_id')
                        ->label('Kampus')
                        ->relationship('campus', 'name')
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(function (Set $set): void {
                            $set('study_program_id', null);
                            $set('class_track_id', null);
                            $set('class_track_ids', []);
                        })
                        ->required(),
                    CheckboxList::make('study_program_ids')
                        ->label('Program studi')
                        ->options(fn (Get $get): array => static::studyProgramOptionsForCampus($get('campus_id')))
                        ->columns(2)
                        ->bulkToggleable()
                        ->visibleOn('create')
                        ->disabled(fn (Get $get): bool => blank($get('campus_id')))
                        ->helperText('Centang satu atau beberapa prodi. Kosongkan jika biaya berlaku untuk semua prodi.')
                        ->columnSpanFull(),
                    Select::make('study_program_id')
                        ->label('Program studi')
                        ->options(fn (Get $get): array => static::studyProgramOptionsForCampus($get('campus_id')))
                        ->searchable()
                        ->preload()
                        ->visibleOn('edit')
                        ->disabled(fn (Get $get): bool => blank($get('campus_id'))),
                    CheckboxList::make('class_track_ids')
                        ->label('Program perkuliahan')
                        ->options(fn (Get $get): array => filled($get('campus_id'))
                            ? ClassTrack::query()
                                ->where('campus_id', $get('campus_id'))
                                ->orderBy('name')
                                ->pluck('name', 'id')
                                ->all()
                            : [])
                        ->columns(2)
                        ->bulkToggleable()
                        ->required()
                        ->visibleOn('create')
                        ->disabled(fn (Get $get): bool => blank($get('campus_id')))
                        ->helperText('Centang satu atau beberapa program perkuliahan. Sistem akan membuat fee scheme untuk masing-masing pilihan.')
                        ->columnSpanFull(),
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
                        ->required()
                        ->visibleOn('edit')
                        ->disabled(fn (Get $get): bool => blank($get('campus_id'))),
                ]),
            Section::make('Model Pembiayaan')
                ->columns(2)
                ->schema([
                    Radio::make('financing_model')
                        ->label('Model pembiayaan')
                        ->options([
                            'spb_spp' => '1. SPB + SPP',
                            'ukt' => '2. UKT',
                        ])
                        ->default('spb_spp')
                        ->live()
                        ->required()
                        ->columnSpanFull(),
                    TextInput::make('registration_fee')
                        ->label('Registration Fee')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->live(onBlur: true)
                        ->visible(fn (Get $get): bool => in_array($get('financing_model'), ['spb_spp', 'ukt'], true))
                        ->required(fn (Get $get): bool => in_array($get('financing_model'), ['spb_spp', 'ukt'], true)),
                    TextInput::make('building_fee')
                        ->label('Development Fee')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->live(onBlur: true)
                        ->visible(fn (Get $get): bool => $get('financing_model') === 'spb_spp')
                        ->required(fn (Get $get): bool => $get('financing_model') === 'spb_spp'),
                    TextInput::make('monthly_tuition_fee')
                        ->label('Tuition Fee')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->live(onBlur: true)
                        ->visible(fn (Get $get): bool => $get('financing_model') === 'spb_spp')
                        ->required(fn (Get $get): bool => $get('financing_model') === 'spb_spp'),
                    Select::make('tuition_semesters')
                        ->label('Banyaknya semester Tuition Fee')
                        ->options(collect(range(1, 8))->mapWithKeys(fn (int $semester): array => [
                            $semester => $semester.' semester',
                        ])->all())
                        ->default(1)
                        ->live()
                        ->visible(fn (Get $get): bool => $get('financing_model') === 'spb_spp')
                        ->required(fn (Get $get): bool => $get('financing_model') === 'spb_spp'),
                    TextInput::make('ukt_total')
                        ->label('Nominal total UKT')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0)
                        ->live(onBlur: true)
                        ->visible(fn (Get $get): bool => $get('financing_model') === 'ukt')
                        ->required(fn (Get $get): bool => $get('financing_model') === 'ukt'),
                    CheckboxList::make('ukt_installment_terms')
                        ->label('Pilihan angsuran UKT')
                        ->options([
                            1 => '1x',
                            12 => '12x',
                            24 => '24x',
                            36 => '36x',
                            48 => '48x',
                        ])
                        ->default([])
                        ->columns(5)
                        ->live()
                        ->visible(fn (Get $get): bool => $get('financing_model') === 'ukt')
                        ->required(fn (Get $get): bool => $get('financing_model') === 'ukt')
                        ->columnSpanFull(),
                    CheckboxList::make('development_installment_terms')
                        ->label('Pilihan angsuran Development Fee')
                        ->options([
                            1 => '1x',
                            12 => '12x',
                            24 => '24x',
                            36 => '36x',
                            48 => '48x',
                        ])
                        ->default([])
                        ->columns(5)
                        ->live()
                        ->visible(fn (Get $get): bool => $get('financing_model') === 'spb_spp')
                        ->required(fn (Get $get): bool => $get('financing_model') === 'spb_spp')
                        ->columnSpanFull(),
                    CheckboxList::make('tuition_installment_terms')
                        ->label('Pilihan angsuran Tuition Fee')
                        ->options([
                            1 => '1x',
                            6 => '6x',
                        ])
                        ->default([])
                        ->columns(2)
                        ->live()
                        ->visible(fn (Get $get): bool => $get('financing_model') === 'spb_spp')
                        ->required(fn (Get $get): bool => $get('financing_model') === 'spb_spp')
                        ->columnSpanFull(),
                    TextInput::make('total_initial_payment')
                        ->label('Total awal')
                        ->numeric()
                        ->prefix('Rp')
                        ->disabled()
                        ->dehydrated()
                        ->helperText('Diisi otomatis dari model pembiayaan.'),
                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->required()
                        ->default(true),
                ]),
            Section::make('Simulasi Angsuran')
                ->schema([
                    Placeholder::make('development_installments_preview')
                        ->label('Development Fee')
                        ->content(fn (Get $get): HtmlString => static::installmentTable(
                            static::buildInstallments((int) $get('building_fee'), static::cleanTerms($get('development_installment_terms') ?? []))
                        ))
                        ->visible(fn (Get $get): bool => $get('financing_model') === 'spb_spp'),
                    Placeholder::make('tuition_installments_preview')
                        ->label('Tuition Fee')
                        ->content(fn (Get $get): HtmlString => static::installmentTable(
                            static::buildInstallments((int) $get('monthly_tuition_fee'), static::cleanTerms($get('tuition_installment_terms') ?? []))
                        ))
                        ->visible(fn (Get $get): bool => $get('financing_model') === 'spb_spp'),
                    Placeholder::make('ukt_installments_preview')
                        ->label('UKT')
                        ->content(fn (Get $get): HtmlString => static::installmentTable(
                            static::buildInstallments((int) $get('ukt_total'), static::cleanTerms($get('ukt_installment_terms') ?? []))
                        ))
                        ->visible(fn (Get $get): bool => $get('financing_model') === 'ukt'),
                    Placeholder::make('monthly_schedule_preview')
                        ->label('Simulasi angsuran bulanan')
                        ->content(fn (Get $get): HtmlString => static::monthlyScheduleTable(
                            static::buildMonthlyScheduleFromForm($get)
                        ))
                        ->visible(fn (Get $get): bool => ! (static::isSuperAdmin() && (bool) $get('uses_custom_installments'))),
                    Toggle::make('uses_custom_installments')
                        ->label('Gunakan custom tabel angsuran')
                        ->helperText('Khusus Super Admin. Aktifkan untuk mengedit simulasi angsuran bulanan secara manual.')
                        ->default(false)
                        ->live()
                        ->afterStateUpdated(function (bool $state, Get $get, Set $set): void {
                            if (! $state || filled($get('custom_installment_schedule_json'))) {
                                return;
                            }

                            $set('custom_installment_schedule_json', static::buildMonthlyScheduleFromForm($get));
                        })
                        ->visible(fn (): bool => static::isSuperAdmin()),
                    Repeater::make('custom_installment_schedule_json')
                        ->label('Simulasi angsuran bulanan')
                        ->schema([
                            TextInput::make('month')
                                ->label('Bulan')
                                ->numeric()
                                ->required(),
                            TextInput::make('registration_fee')
                                ->label('Registration')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0)
                                ->required(),
                            TextInput::make('development_fee')
                                ->label('Development')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0)
                                ->required(),
                            TextInput::make('tuition_fee')
                                ->label('Tuition')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0)
                                ->required(),
                            TextInput::make('ukt')
                                ->label('UKT')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0)
                                ->required(),
                            TextInput::make('total')
                                ->label('Total')
                                ->numeric()
                                ->prefix('Rp')
                                ->default(0)
                                ->required(),
                        ])
                        ->columns(6)
                        ->defaultItems(0)
                        ->addActionLabel('Tambah bulan')
                        ->reorderable()
                        ->visible(fn (Get $get): bool => static::isSuperAdmin() && (bool) $get('uses_custom_installments'))
                        ->columnSpanFull(),
                ]),
            Section::make('Keterangan')
                ->schema([
                    Textarea::make('description')->label('Keterangan')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    public static function mutateFeeData(array $data): array
    {
        $data['registration_fee'] = (int) ($data['registration_fee'] ?? 0);
        $data['building_fee'] = (int) ($data['building_fee'] ?? 0);
        $data['monthly_tuition_fee'] = (int) ($data['monthly_tuition_fee'] ?? 0);
        $data['tuition_semesters'] = max(1, min(8, (int) ($data['tuition_semesters'] ?? 1)));
        $data['ukt_total'] = (int) ($data['ukt_total'] ?? 0);
        $data['uses_custom_installments'] = (bool) ($data['uses_custom_installments'] ?? false);
        $customSchedule = static::normalizeCustomSchedule($data['custom_installment_schedule_json'] ?? []);

        if (($data['financing_model'] ?? 'spb_spp') === 'ukt') {
            $uktTerms = static::cleanTerms($data['ukt_installment_terms'] ?? []);
            $scheduleTerm = max($uktTerms ?: [0]);
            unset($data['ukt_installment_terms'], $data['development_installment_terms'], $data['tuition_installment_terms']);

            $data['building_fee'] = 0;
            $data['monthly_tuition_fee'] = 0;
            $data['tuition_semesters'] = 1;
            $data['total_initial_payment'] = $data['registration_fee'] + $data['ukt_total'];
            $data['development_installments_json'] = null;
            $data['tuition_installments_json'] = null;
            $data['ukt_installments_json'] = static::buildInstallments($data['ukt_total'], $uktTerms);
            $data['installment_schedule_json'] = static::buildMonthlySchedule(
                registrationFee: $data['registration_fee'],
                developmentFee: 0,
                developmentTerm: 0,
                tuitionFee: 0,
                tuitionTerm: 0,
                tuitionSemesters: 1,
                uktTotal: $data['ukt_total'],
                uktTerm: $scheduleTerm,
            );
            $data['custom_installment_schedule_json'] = $customSchedule;

            if ($data['uses_custom_installments'] && $customSchedule !== []) {
                $data['installment_schedule_json'] = $customSchedule;
            }

            return $data;
        }

        $developmentTerms = static::cleanTerms($data['development_installment_terms'] ?? []);
        $tuitionTerms = static::cleanTerms($data['tuition_installment_terms'] ?? []);
        $developmentScheduleTerm = max($developmentTerms ?: [0]);
        $tuitionScheduleTerm = max($tuitionTerms ?: [0]);

        unset($data['ukt_installment_terms'], $data['development_installment_terms'], $data['tuition_installment_terms']);

        $data['ukt_total'] = 0;
        $data['total_initial_payment'] = $data['registration_fee'] + $data['building_fee'] + $data['monthly_tuition_fee'];
        $data['development_installments_json'] = static::buildInstallments($data['building_fee'], $developmentTerms);
        $data['tuition_installments_json'] = static::buildInstallments($data['monthly_tuition_fee'], $tuitionTerms);
        $data['ukt_installments_json'] = null;
        $data['installment_schedule_json'] = static::buildMonthlySchedule(
            registrationFee: $data['registration_fee'],
            developmentFee: $data['building_fee'],
            developmentTerm: $developmentScheduleTerm,
            tuitionFee: $data['monthly_tuition_fee'],
            tuitionTerm: $tuitionScheduleTerm,
            tuitionSemesters: $data['tuition_semesters'],
            uktTotal: 0,
            uktTerm: 0,
        );
        $data['custom_installment_schedule_json'] = $customSchedule;

        if ($data['uses_custom_installments'] && $customSchedule !== []) {
            $data['installment_schedule_json'] = $customSchedule;
        }

        return $data;
    }

    public static function buildInstallments(int $amount, array $terms): array
    {
        return collect($terms)
            ->filter(fn (int $term): bool => $term > 0)
            ->map(fn (int $term): array => [
                'term' => $term,
                'amount_per_installment' => $term > 0 ? (int) ceil($amount / $term) : $amount,
                'total' => $amount,
            ])
            ->all();
    }

    public static function cleanTerms(array $terms): array
    {
        return collect($terms)
            ->map(fn ($term): int => (int) $term)
            ->filter(fn (int $term): bool => $term > 0)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public static function installmentTable(array $rows): HtmlString
    {
        if ($rows === []) {
            return new HtmlString('<p class="text-sm text-slate-500">Centang pilihan angsuran untuk melihat simulasi.</p>');
        }

        $html = '<div class="overflow-hidden rounded-lg border border-slate-200"><table class="w-full text-sm"><thead class="bg-slate-50"><tr><th class="px-3 py-2 text-left font-bold">Angsuran</th><th class="px-3 py-2 text-left font-bold">Nominal per angsuran</th><th class="px-3 py-2 text-left font-bold">Total</th></tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr class="border-t border-slate-200">';
            $html .= '<td class="px-3 py-2 font-semibold">'.$row['term'].'x</td>';
            $html .= '<td class="px-3 py-2">Rp '.number_format($row['amount_per_installment'], 0, ',', '.').'</td>';
            $html .= '<td class="px-3 py-2">Rp '.number_format($row['total'], 0, ',', '.').'</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div>';

        return new HtmlString($html);
    }

    public static function buildMonthlyScheduleFromForm(Get $get): array
    {
        if ($get('financing_model') === 'ukt') {
            $terms = static::cleanTerms($get('ukt_installment_terms') ?? []);

            return static::buildMonthlySchedule(
                registrationFee: (int) $get('registration_fee'),
                developmentFee: 0,
                developmentTerm: 0,
                tuitionFee: 0,
                tuitionTerm: 0,
                tuitionSemesters: 1,
                uktTotal: (int) $get('ukt_total'),
                uktTerm: max($terms ?: [0]),
            );
        }

        $developmentTerms = static::cleanTerms($get('development_installment_terms') ?? []);
        $tuitionTerms = static::cleanTerms($get('tuition_installment_terms') ?? []);

        return static::buildMonthlySchedule(
            registrationFee: (int) $get('registration_fee'),
            developmentFee: (int) $get('building_fee'),
            developmentTerm: max($developmentTerms ?: [0]),
            tuitionFee: (int) $get('monthly_tuition_fee'),
            tuitionTerm: max($tuitionTerms ?: [0]),
            tuitionSemesters: max(1, min(8, (int) ($get('tuition_semesters') ?? 1))),
            uktTotal: 0,
            uktTerm: 0,
        );
    }

    public static function buildMonthlySchedule(
        int $registrationFee,
        int $developmentFee,
        int $developmentTerm,
        int $tuitionFee,
        int $tuitionTerm,
        int $tuitionSemesters,
        int $uktTotal,
        int $uktTerm,
    ): array {
        $tuitionMonths = $tuitionTerm > 0 ? (($tuitionSemesters - 1) * 6) + $tuitionTerm : 0;
        $maxMonth = max([$developmentTerm, $tuitionMonths, $uktTerm, 1]);
        $developmentMonthly = $developmentTerm > 0 ? (int) ceil($developmentFee / $developmentTerm) : 0;
        $tuitionMonthly = $tuitionTerm > 0 ? (int) ceil($tuitionFee / $tuitionTerm) : 0;
        $uktMonthly = $uktTerm > 0 ? (int) ceil($uktTotal / $uktTerm) : 0;

        return collect(range(1, $maxMonth))
            ->map(function (int $month) use ($registrationFee, $developmentTerm, $developmentMonthly, $tuitionTerm, $tuitionMonthly, $tuitionSemesters, $uktTerm, $uktMonthly): array {
                $registration = $month === 1 ? $registrationFee : 0;
                $development = $month <= $developmentTerm ? $developmentMonthly : 0;
                $semesterIndex = intdiv($month - 1, 6);
                $monthInSemester = (($month - 1) % 6) + 1;
                $tuition = $semesterIndex < $tuitionSemesters && $monthInSemester <= $tuitionTerm ? $tuitionMonthly : 0;
                $ukt = $month <= $uktTerm ? $uktMonthly : 0;

                return [
                    'month' => $month,
                    'registration_fee' => $registration,
                    'development_fee' => $development,
                    'tuition_fee' => $tuition,
                    'ukt' => $ukt,
                    'total' => $registration + $development + $tuition + $ukt,
                ];
            })
            ->all();
    }

    public static function monthlyScheduleTable(array $rows): HtmlString
    {
        if ($rows === []) {
            return new HtmlString('<p class="text-sm text-slate-500">Masukkan nominal untuk melihat simulasi bulanan.</p>');
        }

        $html = '<div class="overflow-hidden rounded-lg border border-slate-200"><table class="w-full text-sm"><thead class="bg-slate-50"><tr><th class="px-3 py-2 text-left font-bold">Bulan</th><th class="px-3 py-2 text-left font-bold">Registration</th><th class="px-3 py-2 text-left font-bold">Development</th><th class="px-3 py-2 text-left font-bold">Tuition</th><th class="px-3 py-2 text-left font-bold">UKT</th><th class="px-3 py-2 text-left font-bold">Total</th></tr></thead><tbody>';

        foreach ($rows as $row) {
            $html .= '<tr class="border-t border-slate-200">';
            $html .= '<td class="px-3 py-2 font-semibold">Bulan '.$row['month'].'</td>';
            $html .= '<td class="px-3 py-2">Rp '.number_format($row['registration_fee'], 0, ',', '.').'</td>';
            $html .= '<td class="px-3 py-2">Rp '.number_format($row['development_fee'], 0, ',', '.').'</td>';
            $html .= '<td class="px-3 py-2">Rp '.number_format($row['tuition_fee'], 0, ',', '.').'</td>';
            $html .= '<td class="px-3 py-2">Rp '.number_format($row['ukt'], 0, ',', '.').'</td>';
            $html .= '<td class="px-3 py-2 font-bold">Rp '.number_format($row['total'], 0, ',', '.').'</td>';
            $html .= '</tr>';
        }

        $html .= '</tbody></table></div><p class="mt-2 text-xs text-slate-500">Simulasi bulanan memakai pilihan angsuran terpanjang yang dicentang.</p>';

        return new HtmlString($html);
    }

    public static function normalizeCustomSchedule(array $rows): array
    {
        return collect($rows)
            ->filter(fn (array $row): bool => filled($row['month'] ?? null))
            ->map(fn (array $row): array => [
                'month' => (int) ($row['month'] ?? 0),
                'registration_fee' => (int) ($row['registration_fee'] ?? 0),
                'development_fee' => (int) ($row['development_fee'] ?? 0),
                'tuition_fee' => (int) ($row['tuition_fee'] ?? 0),
                'ukt' => (int) ($row['ukt'] ?? 0),
                'total' => (int) ($row['total'] ?? 0),
            ])
            ->sortBy('month')
            ->values()
            ->all();
    }

    public static function getEloquentQuery(): Builder
    {
        return FilamentResourceScope::applyCampusScope(parent::getEloquentQuery());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('campus.name')->searchable()->sortable(),
                TextColumn::make('studyProgram.name')->placeholder('Semua prodi'),
                TextColumn::make('classTrack.name')->label('Program perkuliahan')->placeholder('Semua program'),
                TextColumn::make('financing_model')
                    ->label('Model')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'ukt' ? 'UKT' : 'SPB + SPP'),
                TextColumn::make('registration_fee')->money('IDR')->sortable(),
                TextColumn::make('building_fee')->label('Development')->money('IDR')->sortable(),
                TextColumn::make('monthly_tuition_fee')->label('Tuition')->money('IDR')->sortable(),
                TextColumn::make('ukt_total')->label('UKT')->money('IDR')->sortable(),
                TextColumn::make('total_initial_payment')->money('IDR')->sortable(),
                IconColumn::make('is_active')->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('campus_id')->relationship('campus', 'name'),
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFeeSchemes::route('/'),
            'create' => Pages\CreateFeeScheme::route('/create'),
            'edit' => Pages\EditFeeScheme::route('/{record}/edit'),
        ];
    }
}
