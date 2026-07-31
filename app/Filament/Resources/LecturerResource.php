<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LecturerResource\Pages;
use App\Models\Lecturer;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LecturerResource extends Resource
{
    protected static ?string $model = Lecturer::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationGroup = 'SIAKAD';

    protected static ?string $navigationLabel = 'Dosen';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessAcademic();
    }

    public static function getEloquentQuery(): Builder
    {
        return FilamentResourceScope::applyCampusScope(parent::getEloquentQuery());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('campus_id')->label('Kampus')->relationship('campus', 'name')->searchable()->preload()->required(),
            TextInput::make('name')->label('Nama Dosen')->required()->maxLength(255),
            TextInput::make('nidn')->label('NIDN')->maxLength(32),
            TextInput::make('email')->label('Email')->email()->maxLength(255),
            TextInput::make('phone')->label('No. HP')->maxLength(32),
            Select::make('status')->label('Status')->options([
                'active' => 'Aktif',
                'inactive' => 'Nonaktif',
            ])->default('active')->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('campus.name')->label('Kampus')->searchable()->sortable(),
                TextColumn::make('name')->label('Nama')->searchable()->sortable(),
                TextColumn::make('nidn')->label('NIDN')->searchable(),
                TextColumn::make('email')->label('Email')->searchable(),
                TextColumn::make('phone')->label('No. HP'),
                TextColumn::make('course_classes_count')->counts('courseClasses')->label('Kelas Diampu'),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLecturers::route('/'),
            'create' => Pages\CreateLecturer::route('/create'),
            'edit' => Pages\EditLecturer::route('/{record}/edit'),
        ];
    }
}
