<?php

namespace App\Filament\Resources;

use App\Enums\AcademicStatus;
use App\Filament\Resources\SiakadStudentResource\Pages;
use App\Models\StudentBiodata;
use App\Support\FilamentResourceScope;
use App\Support\SiakadCampusResolver;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class SiakadStudentResource extends Resource
{
    protected static ?string $model = StudentBiodata::class;

    protected static ?string $slug = 'students';

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'SIAKAD';

    protected static ?string $navigationLabel = 'Mahasiswa';

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessAcademic();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            ...StudentBiodataResourceSchema::form(),
            Section::make('Status Akademik')
                ->schema([
                    Select::make('academic_status')
                        ->label('Status Akademik')
                        ->options(AcademicStatus::class)
                        ->default(AcademicStatus::Aktif)
                        ->required(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $table = StudentBiodataResourceSchema::table($table);

        return $table
            ->columns([
                ...$table->getColumns(),
                TextColumn::make('academic_status')->label('Status Akademik')->badge(),
            ])
            ->filters([
                ...$table->getFilters(),
                SelectFilter::make('academic_status')->label('Status Akademik')->options(AcademicStatus::class),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('campus_id', SiakadCampusResolver::current()?->id ?? 0);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSiakadStudents::route('/'),
            'edit' => Pages\EditSiakadStudent::route('/{record}/edit'),
        ];
    }
}
