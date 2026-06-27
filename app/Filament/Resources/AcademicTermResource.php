<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AcademicTermResource\Pages;
use App\Models\AcademicTerm;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AcademicTermResource extends Resource
{
    protected static ?string $model = AcademicTerm::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'SIAKAD';

    protected static ?string $navigationLabel = 'Tahun Akademik';

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('campus_id')->label('Kampus')->relationship('campus', 'name')->searchable()->preload()->required(),
            TextInput::make('name')->label('Nama periode')->required()->maxLength(255),
            TextInput::make('academic_year')->label('Tahun akademik')->placeholder('2026/2027')->required()->maxLength(16),
            Select::make('semester_type')->label('Semester')->options([
                'ganjil' => 'Ganjil',
                'genap' => 'Genap',
                'pendek' => 'Pendek',
            ])->required(),
            DatePicker::make('starts_at')->label('Mulai'),
            DatePicker::make('ends_at')->label('Selesai'),
            Select::make('status')->label('Status')->options([
                'draft' => 'Draft',
                'active' => 'Aktif',
                'closed' => 'Ditutup',
            ])->default('draft')->required(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('campus.name')->label('Kampus')->searchable()->sortable(),
                TextColumn::make('name')->label('Periode')->searchable()->sortable(),
                TextColumn::make('academic_year')->label('Tahun')->sortable(),
                TextColumn::make('semester_type')->label('Semester')->badge(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('starts_at')->label('Mulai')->date(),
                TextColumn::make('ends_at')->label('Selesai')->date(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAcademicTerms::route('/'),
            'create' => Pages\CreateAcademicTerm::route('/create'),
            'edit' => Pages\EditAcademicTerm::route('/{record}/edit'),
        ];
    }
}
