<?php

namespace App\Filament\Resources;

use App\Enums\CampusStatus;
use App\Filament\Resources\CampusResource\Pages;
use App\Models\Campus;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CampusResource extends Resource
{
    protected static ?string $model = Campus::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?string $navigationGroup = 'Master Data';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessMasterData();
    }

    public static function canCreate(): bool
    {
        return FilamentResourceScope::isSuperAdmin();
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return FilamentResourceScope::isSuperAdmin();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Identitas Kampus')
                ->description('Data dasar yang tampil di portal publik dan dashboard PMB.')
                ->columns(2)
                ->schema([
                    TextInput::make('name')
                        ->label('Nama kampus')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (string $operation, $state, callable $set): void {
                            if ($operation === 'create') {
                                $set('slug', \Illuminate\Support\Str::slug($state));
                            }
                        }),
                    TextInput::make('slug')
                        ->label('Slug')
                        ->helperText('Contoh: kampus-mahera. Dipakai untuk URL publik (bukan tagline).')
                        ->required()
                        ->maxLength(255)
                        ->alphaDash()
                        ->unique(ignoreRecord: true),
                    TextInput::make('subdomain')
                        ->label('Subdomain')
                        ->helperText('Opsional. Contoh: mahera. Otomatis menjadi https://mahera.kampus.media')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    TextInput::make('custom_domain')
                        ->label('Custom domain')
                        ->helperText('Opsional. Contoh: pmb.kampus.ac.id')
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            CampusStatus::Active->value => 'Aktif',
                            CampusStatus::Inactive->value => 'Nonaktif',
                        ])
                        ->default(CampusStatus::Active->value)
                        ->required(),
                ]),
            Section::make('Lokasi & Logo')
                ->description('Alamat dan logo kampus untuk tampilan publik.')
                ->columns(2)
                ->schema([
                    Textarea::make('address')
                        ->label('Alamat')
                        ->rows(3)
                        ->columnSpanFull(),
                    TextInput::make('city')
                        ->label('Kota/Kabupaten')
                        ->maxLength(255),
                    TextInput::make('province')
                        ->label('Provinsi')
                        ->maxLength(255),
                    FileUpload::make('logo_path')
                        ->label('Logo kampus')
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('campus-logos')
                        ->visibility('public')
                        ->maxSize(2048)
                        ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/webp'])
                        ->maxFiles(1)
                        ->fetchFileInformation(false)
                        ->deletable()
                        ->downloadable()
                        ->openable()
                        ->previewable()
                        ->helperText('Upload PNG, JPG, atau WebP maksimal 2 MB.')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return FilamentResourceScope::applyCampusScope(parent::getEloquentQuery(), 'id');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                ImageColumn::make('logo_path')
                    ->label('Logo')
                    ->disk('public')
                    ->square()
                    ->defaultImageUrl(url('/favicon.ico')),
                TextColumn::make('name')->searchable()->sortable(),
                TextColumn::make('slug')->searchable(),
                TextColumn::make('subdomain')->searchable(),
                TextColumn::make('custom_domain')->searchable(),
                TextColumn::make('status')->badge(),
                TextColumn::make('updated_at')->dateTime()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options([
                    CampusStatus::Active->value => 'Active',
                    CampusStatus::Inactive->value => 'Inactive',
                ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCampuses::route('/'),
            'create' => Pages\CreateCampus::route('/create'),
            'edit' => Pages\EditCampus::route('/{record}/edit'),
        ];
    }
}
