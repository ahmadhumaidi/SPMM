<?php

namespace App\Filament\Resources;

use App\Enums\CampusStatus;
use App\Filament\Resources\ClassTrackResource\Pages;
use App\Models\ClassTrack;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClassTrackResource extends Resource
{
    protected static ?string $model = ClassTrack::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Master Data';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessMasterData();
    }

    protected static ?string $navigationLabel = 'Program Perkuliahan';

    protected static ?string $modelLabel = 'Program Perkuliahan';

    protected static ?string $pluralModelLabel = 'Program Perkuliahan';

    public static function programOptions(): array
    {
        return [
            'Program Kuliah Professional' => 'Program Kuliah Professional',
            'Full Online' => 'Full Online',
            'Hybird Learning' => 'Hybird Learning',
            'RPL' => 'RPL',
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return FilamentResourceScope::applyCampusScope(parent::getEloquentQuery());
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Program Perkuliahan')
                ->description('Pilih program yang tersedia untuk kampus ini.')
                ->columns(2)
                ->schema([
                    Select::make('campus_id')
                        ->label('Kampus')
                        ->relationship('campus', 'name')
                        ->required(),
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            CampusStatus::Active->value => 'Aktif',
                            CampusStatus::Inactive->value => 'Nonaktif',
                        ])
                        ->default(CampusStatus::Active->value)
                        ->required(),
                    CheckboxList::make('names')
                        ->label('Nama program')
                        ->options(static::programOptions())
                        ->columns(2)
                        ->bulkToggleable()
                        ->required()
                        ->visibleOn('create')
                        ->columnSpanFull(),
                    Select::make('name')
                        ->label('Nama program')
                        ->options(static::programOptions())
                        ->required()
                        ->visibleOn('edit'),
                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(3)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('campus.name')->label('Kampus')->sortable()->searchable(),
                TextColumn::make('name')->label('Program')->searchable()->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([Tables\Filters\SelectFilter::make('campus_id')->label('Kampus')->relationship('campus', 'name')])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClassTracks::route('/'),
            'create' => Pages\CreateClassTrack::route('/create'),
            'edit' => Pages\EditClassTrack::route('/{record}/edit'),
        ];
    }
}
