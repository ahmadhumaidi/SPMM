<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LmsMaterialResource\Pages;
use App\Models\LmsMaterial;
use App\Models\LmsModule;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LmsMaterialResource extends Resource
{
    protected static ?string $model = LmsMaterial::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';

    protected static ?string $navigationGroup = 'LMS Kampus';

    protected static ?string $navigationLabel = 'Materi Kuliah';

    protected static ?int $navigationSort = 2;

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('lms_module_id')
                ->label('Modul')
                ->options(fn (): array => LmsModule::query()
                    ->with('courseClass.course')
                    ->get()
                    ->mapWithKeys(fn (LmsModule $module): array => [
                        $module->id => ($module->courseClass?->course?->name ?? 'Mata kuliah').' / '.$module->title,
                    ])
                    ->all())
                ->searchable()
                ->required(),
            TextInput::make('title')->label('Judul Materi')->required()->maxLength(255),
            Select::make('material_type')->label('Tipe Materi')->options([
                'file' => 'File',
                'link' => 'Link',
                'text' => 'Teks',
                'video' => 'Video',
            ])->default('file')->required(),
            TextInput::make('sort_order')->label('Urutan')->numeric()->default(1)->required(),
            FileUpload::make('file_path')->label('Upload File')->directory('lms/materials')->downloadable()->openable(),
            TextInput::make('external_url')->label('Link Materi')->url()->maxLength(255),
            Select::make('status')->label('Status')->options([
                'draft' => 'Draft',
                'published' => 'Terbit',
                'archived' => 'Arsip',
            ])->default('published')->required(),
            Textarea::make('content')->label('Konten / Catatan Materi')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('module.courseClass.course.name')->label('Mata Kuliah')->searchable(),
                TextColumn::make('module.title')->label('Modul')->searchable(),
                TextColumn::make('title')->label('Materi')->searchable(),
                TextColumn::make('material_type')->label('Tipe')->badge(),
                TextColumn::make('sort_order')->label('Urutan')->sortable(),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLmsMaterials::route('/'),
            'create' => Pages\CreateLmsMaterial::route('/create'),
            'edit' => Pages\EditLmsMaterial::route('/{record}/edit'),
        ];
    }
}
