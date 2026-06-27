<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudentNumberResource\Pages;
use App\Models\StudentNumber;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudentNumberResource extends Resource
{
    protected static ?string $model = StudentNumber::class;

    protected static ?string $navigationIcon = 'heroicon-o-numbered-list';

    protected static ?string $navigationGroup = 'PDDIKTI';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessPddikti();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('lead_id')->relationship('lead', 'full_name')->required()->unique(ignoreRecord: true),
            TextInput::make('nim')->required()->maxLength(64)->unique(ignoreRecord: true),
            Select::make('issued_by_user_id')->relationship('issuedBy', 'name')->required(),
            DateTimePicker::make('issued_at')->required()->default(now()),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('nim')->searchable()->sortable(),
                TextColumn::make('lead.full_name')->searchable(),
                TextColumn::make('lead.campus.name')->sortable(),
                TextColumn::make('issuedBy.name'),
                TextColumn::make('issued_at')->dateTime()->sortable(),
            ])
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudentNumbers::route('/'),
            'create' => Pages\CreateStudentNumber::route('/create'),
            'edit' => Pages\EditStudentNumber::route('/{record}/edit'),
        ];
    }
}
