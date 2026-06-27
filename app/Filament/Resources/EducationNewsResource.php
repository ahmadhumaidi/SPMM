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
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Notifications\Notification;
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
        $query = parent::getEloquentQuery()->with(['campuses', 'publishedBy']);

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
                    TextInput::make('topik_artikel')
                        ->label('Topik Artikel')
                        ->maxLength(255),
                    TextInput::make('keyword_utama')
                        ->label('Keyword Utama')
                        ->maxLength(255),
                    TextInput::make('author_name')
                        ->label('Penulis')
                        ->default('Kampus Media')
                        ->maxLength(120),
                    DateTimePicker::make('published_at')
                        ->label('Tanggal Publish')
                        ->seconds(false)
                        ->default(now()),
                    Select::make('published_by_user_id')
                        ->label('Diverifikasi publish oleh')
                        ->relationship('publishedBy', 'name')
                        ->disabled()
                        ->dehydrated(false)
                        ->placeholder('Belum dipublish'),
                    FileUpload::make('image_path')
                        ->label('Gambar Berita')
                        ->image()
                        ->imageEditor()
                        ->disk('public')
                        ->directory('education-news')
                        ->visibility('public')
                        ->maxFiles(1)
                        ->fetchFileInformation(false)
                        ->deletable()
                        ->downloadable()
                        ->openable()
                        ->previewable()
                        ->columnSpanFull(),
                    Textarea::make('excerpt')
                        ->label('Ringkasan')
                        ->rows(3)
                        ->maxLength(500)
                        ->columnSpanFull(),
                    Textarea::make('meta_description')
                        ->label('Meta Description')
                        ->rows(2)
                        ->maxLength(500)
                        ->helperText('Disarankan maksimal 160 karakter untuk SEO.')
                        ->columnSpanFull(),
                    RichEditor::make('content')
                        ->label('Isi Berita')
                        ->columnSpanFull(),
                ]),
            Section::make('Sumber & AI')
                ->columns(2)
                ->collapsed()
                ->schema([
                    TextInput::make('source_name')
                        ->label('Nama sumber')
                        ->maxLength(255),
                    TextInput::make('source_url')
                        ->label('URL sumber')
                        ->url()
                        ->maxLength(2048),
                    DateTimePicker::make('source_published_at')
                        ->label('Tanggal sumber')
                        ->seconds(false),
                    Toggle::make('generated_by_ai')
                        ->label('Dibuat AI')
                        ->disabled(),
                    Toggle::make('ai_generated')
                        ->label('AI Generated')
                        ->disabled(),
                    TextInput::make('tipe_konten')
                        ->label('Tipe Konten')
                        ->maxLength(120),
                    TextInput::make('keyword_tambahan')
                        ->label('Keyword Tambahan')
                        ->maxLength(500),
                    TextInput::make('target_pembaca')
                        ->label('Target Pembaca')
                        ->maxLength(255),
                    TextInput::make('nama_kampus')
                        ->label('Nama Kampus')
                        ->maxLength(255),
                    TextInput::make('lokasi')
                        ->label('Lokasi')
                        ->maxLength(255),
                    TextInput::make('gaya_bahasa')
                        ->label('Gaya Bahasa')
                        ->maxLength(120),
                    TextInput::make('tipe_artikel')
                        ->label('Tipe Artikel')
                        ->maxLength(120),
                    TextInput::make('panjang_artikel')
                        ->label('Panjang Artikel')
                        ->maxLength(120),
                    DateTimePicker::make('ai_generated_at')
                        ->label('Waktu generate AI')
                        ->seconds(false),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
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
                TextColumn::make('ai_generated')
                    ->label('AI')
                    ->badge()
                    ->formatStateUsing(fn (bool $state): string => $state ? 'AI' : 'Manual')
                    ->color(fn (bool $state): string => $state ? 'info' : 'gray'),
                TextColumn::make('keyword_utama')
                    ->label('Keyword')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('source_name')
                    ->label('Sumber')
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('published_at')
                    ->label('Publish')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('publishedBy.name')
                    ->label('Diverifikasi oleh')
                    ->placeholder('-')
                    ->toggleable(),
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
                Tables\Actions\Action::make('publish')
                    ->label('Publish')
                    ->visible(fn (EducationNews $record): bool => $record->status !== 'published')
                    ->requiresConfirmation()
                    ->action(function (EducationNews $record): void {
                        $record->update([
                            'status' => 'published',
                            'published_at' => $record->published_at ?? now(),
                            'published_by_user_id' => auth()->id(),
                        ]);

                        Notification::make()
                            ->title('Berita dipublish')
                            ->success()
                            ->send();
                    }),
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
