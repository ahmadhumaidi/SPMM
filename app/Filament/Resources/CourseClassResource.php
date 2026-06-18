<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseClassResource\Pages;
use App\Models\CourseClass;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CourseClassResource extends Resource
{
    protected static ?string $model = CourseClass::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'SIAKAD';

    protected static ?string $navigationLabel = 'Kelas & Jadwal';

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('academic_term_id')->label('Tahun Akademik')->relationship('academicTerm', 'name')->searchable()->preload()->required(),
            Select::make('course_id')->label('Mata Kuliah')->relationship('course', 'name')->searchable()->preload()->required(),
            Select::make('class_track_id')->label('Program Perkuliahan')->relationship('classTrack', 'name')->searchable()->preload(),
            TextInput::make('class_name')->label('Nama Kelas')->default('A')->required(),
            TextInput::make('lecturer_name')->label('Dosen Pengampu')->maxLength(255),
            Select::make('day_of_week')->label('Hari')->options([
                'Senin' => 'Senin',
                'Selasa' => 'Selasa',
                'Rabu' => 'Rabu',
                'Kamis' => 'Kamis',
                'Jumat' => 'Jumat',
                'Sabtu' => 'Sabtu',
                'Minggu' => 'Minggu',
            ]),
            TimePicker::make('starts_at')->label('Jam Mulai')->seconds(false),
            TimePicker::make('ends_at')->label('Jam Selesai')->seconds(false),
            TextInput::make('room')->label('Ruang')->maxLength(128),
            TextInput::make('capacity')->label('Kapasitas')->numeric(),
            Select::make('status')->label('Status')->options(['open' => 'Dibuka', 'closed' => 'Ditutup'])->default('open')->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('academicTerm.name')->label('Periode')->searchable(),
                TextColumn::make('course.code')->label('Kode')->searchable(),
                TextColumn::make('course.name')->label('Mata Kuliah')->searchable(),
                TextColumn::make('class_name')->label('Kelas'),
                TextColumn::make('lecturer_name')->label('Dosen')->searchable(),
                TextColumn::make('day_of_week')->label('Hari'),
                TextColumn::make('starts_at')->label('Mulai'),
                TextColumn::make('ends_at')->label('Selesai'),
                TextColumn::make('status')->badge(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourseClasses::route('/'),
            'create' => Pages\CreateCourseClass::route('/create'),
            'edit' => Pages\EditCourseClass::route('/{record}/edit'),
        ];
    }
}
