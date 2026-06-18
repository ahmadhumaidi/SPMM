<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LmsModuleResource\Pages;
use App\Models\CourseClass;
use App\Models\LmsModule;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LmsModuleResource extends Resource
{
    protected static ?string $model = LmsModule::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationGroup = 'LMS Kampus';

    protected static ?string $navigationLabel = 'Modul Pembelajaran';

    protected static ?int $navigationSort = 1;

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('course_class_id')
                ->label('Kelas Kuliah')
                ->options(fn (): array => CourseClass::query()
                    ->with(['course', 'academicTerm'])
                    ->get()
                    ->mapWithKeys(fn (CourseClass $class): array => [
                        $class->id => trim(($class->course?->code ? $class->course->code.' - ' : '').($class->course?->name ?? 'Mata kuliah').' / Kelas '.$class->class_name.' / '.($class->academicTerm?->name ?? '')),
                    ])
                    ->all())
                ->searchable()
                ->required(),
            TextInput::make('title')->label('Judul Modul')->required()->maxLength(255),
            TextInput::make('sort_order')->label('Urutan')->numeric()->default(1)->required(),
            Select::make('status')->label('Status')->options([
                'draft' => 'Draft',
                'published' => 'Terbit',
                'archived' => 'Arsip',
            ])->default('published')->required(),
            Textarea::make('description')->label('Deskripsi')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('courseClass.course.name')->label('Mata Kuliah')->searchable(),
                TextColumn::make('courseClass.class_name')->label('Kelas'),
                TextColumn::make('title')->label('Modul')->searchable(),
                TextColumn::make('materials_count')->counts('materials')->label('Materi'),
                TextColumn::make('assignments_count')->counts('assignments')->label('Tugas'),
                TextColumn::make('sort_order')->label('Urutan')->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLmsModules::route('/'),
            'create' => Pages\CreateLmsModule::route('/create'),
            'edit' => Pages\EditLmsModule::route('/{record}/edit'),
        ];
    }
}
