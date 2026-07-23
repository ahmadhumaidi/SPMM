<?php

namespace App\Filament\Resources;

use App\Models\StudyProgram;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class StudentBiodataResourceSchema
{
    public static function form(): array
    {
        return [
            Section::make('Data Pendaftaran')
                ->columns(2)
                ->schema([
                    Select::make('campus_id')
                        ->label('Kampus')
                        ->relationship('campus', 'name', fn (Builder $query): Builder => FilamentResourceScope::applyCampusScope($query->orderBy('name'), 'campuses.id'))
                        ->searchable()
                        ->preload()
                        ->live()
                        ->afterStateUpdated(fn (Set $set): mixed => $set('study_program_id', null))
                        ->required(),
                    TextInput::make('name')
                        ->label('Nama')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('selection_number')
                        ->label('No. Seleksi')
                        ->disabled()
                        ->dehydrated(false)
                        ->maxLength(64),
                    TextInput::make('student_number')
                        ->label('No. Pokok')
                        ->maxLength(64),
                    Select::make('study_program_id')
                        ->label('Program Studi')
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
                        ->disabled(fn (Get $get): bool => blank($get('campus_id'))),
                    Select::make('group')
                        ->label('Kelompok')
                        ->options([
                            'Reguler' => 'Reguler',
                            'Karyawan' => 'Karyawan',
                            'Eksekutif' => 'Eksekutif',
                            'Online' => 'Online',
                            'RPL' => 'RPL',
                        ])
                        ->searchable(),
                    Select::make('cohort_year')
                        ->label('Angkatan')
                        ->options(collect(range((int) date('Y') + 1, 2015))->mapWithKeys(fn (int $year): array => [
                            (string) $year => (string) $year,
                        ])->all())
                        ->searchable(),
                    Select::make('financial_status')
                        ->label('Status Keuangan')
                        ->options([
                            'Belum bayar' => 'Belum bayar',
                            'RR' => 'RR',
                            'Lunas' => 'Lunas',
                            'Tunggakan' => 'Tunggakan',
                        ])
                        ->searchable(),
                    Select::make('entry_type')
                        ->label('Baru/Pindahan')
                        ->options([
                            'Baru' => 'Baru',
                            'Pindahan' => 'Pindahan',
                        ]),
                    Select::make('class_time')
                        ->label('Waktu Kuliah')
                        ->options([
                            'Pagi' => 'Pagi',
                            'Siang' => 'Siang',
                            'Sore' => 'Sore',
                            'Malam' => 'Malam',
                            'Shift' => 'Shift',
                            'Online' => 'Online',
                            'Hybrid' => 'Hybrid',
                        ])
                        ->searchable(),
                    Select::make('information_source')
                        ->label('Sumber Informasi')
                        ->options(self::informationSourceOptions())
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
                        ->disk('public')
                        ->directory('student-photos')
                        ->imageEditor()
                        ->fetchFileInformation(false)
                        ->columnSpanFull(),
                ]),
            Section::make('Biodata Mahasiswa')
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
                        ])
                        ->searchable(),
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
            Section::make('Data Keluarga')
                ->columns(2)
                ->schema([
                    TextInput::make('mother_name')->label('Nama Ibu Kandung')->maxLength(255),
                    TextInput::make('family_name')->label('Nama kontak keluarga')->maxLength(255),
                    TextInput::make('family_number')->label('No HP/WA kontak keluarga')->maxLength(64),
                    TextInput::make('family_relationship')->label('Hubungan')->maxLength(255),
                ]),
            Section::make('Data Pekerjaan')
                ->columns(2)
                ->schema([
                    TextInput::make('company_name')->label('Perusahaan/Instansi')->maxLength(255),
                    TextInput::make('job_title')->label('Jabatan')->maxLength(255),
                    Textarea::make('company_address')->label('Alamat Perusahaan')->rows(3)->columnSpanFull(),
                    TextInput::make('company_city')->label('Kota/Kabupaten')->maxLength(255),
                    TextInput::make('company_postal_code')->label('Kode Pos')->maxLength(16),
                    TextInput::make('company_phone')->label('No. Telepon')->maxLength(32),
                ]),
            Section::make('Data Biaya')
                ->columns(2)
                ->schema([
                    TextInput::make('development_contribution')
                        ->label('Sumbangan Pengembangan')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0),
                    TextInput::make('semester_education_contribution')
                        ->label('Sumbangan Pendidikan Per Semester')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0),
                    TextInput::make('almamater_jacket_fee')
                        ->label('Jaket Almamater')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0),
                    TextInput::make('form_fee')
                        ->label('Formulir')
                        ->numeric()
                        ->prefix('Rp')
                        ->default(0),
                ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('photo_path')->label('Foto')->circular(),
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('student_number')->label('No. Pokok')->searchable(),
                TextColumn::make('selection_number')->label('No. Seleksi')->searchable()->toggleable(),
                TextColumn::make('campus.name')->label('Kampus')->sortable(),
                TextColumn::make('studyProgram.name')->label('Program Studi')->searchable(),
                TextColumn::make('cohort_year')->label('Angkatan')->sortable(),
                TextColumn::make('financial_status')->label('Status Keuangan')->badge(),
                TextColumn::make('information_source')->label('Sumber Informasi')->toggleable(),
                TextColumn::make('affiliator_code')->label('Kode Affiliator')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('entry_type')->label('Baru/Pindahan')->toggleable(),
                TextColumn::make('whatsapp_number')->label('No. WA')->searchable()->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('campus_id')
                    ->label('Kampus')
                    ->relationship('campus', 'name', fn (Builder $query): Builder => FilamentResourceScope::applyCampusScope($query->orderBy('name'), 'campuses.id')),
                Tables\Filters\SelectFilter::make('financial_status')->label('Status Keuangan')->options([
                    'Belum bayar' => 'Belum bayar',
                    'RR' => 'RR',
                    'Lunas' => 'Lunas',
                    'Tunggakan' => 'Tunggakan',
                ]),
                Tables\Filters\SelectFilter::make('cohort_year')->label('Angkatan')->options(
                    collect(range((int) date('Y') + 1, 2015))->mapWithKeys(fn (int $year): array => [
                        (string) $year => (string) $year,
                    ])->all()
                ),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function informationSourceOptions(): array
    {
        return [
            'Tiktok' => 'Tiktok',
            'Instagram' => 'Instagram',
            'Facebook' => 'Facebook',
            'Youtube' => 'Youtube',
            'Iklan' => 'Iklan',
            'Teman' => 'Teman',
            'Saudara' => 'Saudara',
            'Rekan Kerja' => 'Rekan Kerja',
            'Brosur' => 'Brosur',
            'Spanduk' => 'Spanduk',
            'Affiliator' => 'Affiliator',
            'Lainnya' => 'Lainnya',
        ];
    }
}
