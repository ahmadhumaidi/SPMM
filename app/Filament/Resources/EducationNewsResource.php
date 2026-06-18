<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EducationNewsResource\Pages;
use App\Models\EducationNews;
use App\Support\FilamentResourceScope;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class EducationNewsResource extends Resource
{
    protected static ?string $model = EducationNews::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';

    protected static ?string $navigationGroup = 'Portal Publik';

    protected static ?string $navigationLabel = 'Berita Pendidikan';

    protected static ?string $modelLabel = 'Berita Pendidikan';

    protected static ?string $pluralModelLabel = 'Berita Pendidikan';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessPortalPublic();
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with('campuses');

        if (FilamentResourceScope::isSuperAdmin()) {
            return $query;
        }

        return $query->whereHas('campuses', fn (Builder $campusQuery): Builder => FilamentResourceScope::applyCampusScope($campusQuery, 'campuses.id'));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Konten Berita')
                ->columns(2)
                ->schema([
                    Select::make('campuses')
                        ->label('Kampus tujuan')
                        ->relationship('campuses', 'name', fn (Builder $query): Builder => FilamentResourceScope::applyCampusScope($query->orderBy('name'), 'campuses.id'))
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->required(fn (): bool => ! FilamentResourceScope::isSuperAdmin())
                        ->helperText('Pilih satu atau beberapa kampus. Kosongkan jika berita umum untuk semua kampus.'),
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            'draft' => 'Draft',
                            'published' => 'Published',
                            'archived' => 'Archived',
                        ])
                        ->default('draft')
                        ->required(),
                    TextInput::make('title')
                        ->label('Judul')
                        ->required()
                        ->maxLength(255)
                        ->live(onBlur: true)
                        ->afterStateUpdated(fn (string $operation, ?string $state, callable $set): mixed => $operation === 'create' && filled($state)
                            ? $set('slug', Str::slug($state))
                            : null),
                    TextInput::make('slug')
                        ->label('Slug')
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),
                    TextInput::make('category')
                        ->label('Kategori')
                        ->default('Pendidikan')
                        ->maxLength(120),
                    TextInput::make('author_name')
                        ->label('Penulis')
                        ->default('Kampus Nusantara')
                        ->maxLength(120),
                    DateTimePicker::make('published_at')
                        ->label('Tanggal Publish')
                        ->seconds(false)
                        ->default(now()),
                    FileUpload::make('image_path')
                        ->label('Gambar Berita')
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('education-news')
                        ->visibility('public')
                        ->columnSpanFull(),
                    Textarea::make('excerpt')
                        ->label('Ringkasan')
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpanFull(),
                    RichEditor::make('content')
                        ->label('Isi Berita')
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Gambar')
                    ->disk('public')
                    ->square(),
                TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('campuses.name')
                    ->label('Kampus')
                    ->placeholder('Umum')
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->expandableLimitedList()
                    ->searchable(),
                TextColumn::make('category')
                    ->label('Kategori')
                    ->badge(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                TextColumn::make('published_at')
                    ->label('Publish')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'published' => 'Published',
                        'archived' => 'Archived',
                    ]),
                Tables\Filters\SelectFilter::make('campuses')
                    ->label('Kampus')
                    ->relationship('campuses', 'name', fn (Builder $query): Builder => FilamentResourceScope::applyCampusScope($query->orderBy('name'), 'campuses.id'))
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEducationNews::route('/'),
            'create' => Pages\CreateEducationNews::route('/create'),
            'edit' => Pages\EditEducationNews::route('/{record}/edit'),
        ];
    }
}
