<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CourseResource\Pages;
use App\Models\Course;
use App\Models\StudyProgram;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CourseResource extends Resource
{
    protected static ?string $model = Course::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'SIAKAD';

    protected static ?string $navigationLabel = 'Mata Kuliah';

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('campus_id')->label('Kampus')->relationship('campus', 'name')->searchable()->preload()->live()->required(),
            Select::make('study_program_id')
                ->label('Program Studi')
                ->options(fn (Get $get): array => filled($get('campus_id')) ? StudyProgram::query()->where('campus_id', $get('campus_id'))->orderBy('name')->pluck('name', 'id')->all() : [])
                ->searchable()
                ->preload(),
            TextInput::make('code')->label('Kode MK')->required()->maxLength(32),
            TextInput::make('name')->label('Nama Mata Kuliah')->required()->maxLength(255),
            TextInput::make('credits')->label('SKS')->numeric()->default(3)->required(),
            TextInput::make('semester_level')->label('Semester')->numeric(),
            Select::make('course_type')->label('Tipe')->options(['wajib' => 'Wajib', 'pilihan' => 'Pilihan'])->default('wajib')->required(),
            Select::make('status')->label('Status')->options(['active' => 'Aktif', 'inactive' => 'Nonaktif'])->default('active')->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('campus.name')->label('Kampus')->searchable(),
                TextColumn::make('studyProgram.name')->label('Prodi')->searchable(),
                TextColumn::make('code')->label('Kode')->searchable()->sortable(),
                TextColumn::make('name')->label('Mata Kuliah')->searchable()->sortable(),
                TextColumn::make('credits')->label('SKS')->sortable(),
                TextColumn::make('semester_level')->label('Semester')->sortable(),
                TextColumn::make('status')->badge(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCourses::route('/'),
            'create' => Pages\CreateCourse::route('/create'),
            'edit' => Pages\EditCourse::route('/{record}/edit'),
        ];
    }
}
