<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OldStudentBiodataResource\Pages;
use App\Models\StudentBiodata;
use App\Support\FilamentResourceScope;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class OldStudentBiodataResource extends Resource
{
    protected static ?string $model = StudentBiodata::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'Mahasiswa Lama';

    protected static ?string $navigationLabel = 'Biodata';

    protected static ?int $navigationSort = 1;


    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessStudentRecords();
    }

    public static function form(Form $form): Form
    {
        return $form->schema(StudentBiodataResourceSchema::form());
    }

    public static function table(Table $table): Table
    {
        return StudentBiodataResourceSchema::table($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return FilamentResourceScope::applyCampusScope(
            parent::getEloquentQuery()->where('student_type', 'old')
        );
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOldStudentBiodatas::route('/'),
            'edit' => Pages\EditOldStudentBiodata::route('/{record}/edit'),
        ];
    }
}
