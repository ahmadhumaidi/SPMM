<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LmsAssignmentResource\Pages;
use App\Models\LmsAssignment;
use App\Models\LmsModule;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LmsAssignmentResource extends Resource
{
    protected static ?string $model = LmsAssignment::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'LMS Kampus';

    protected static ?string $navigationLabel = 'Tugas Kuliah';

    protected static ?int $navigationSort = 3;

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
            TextInput::make('title')->label('Judul Tugas')->required()->maxLength(255),
            DateTimePicker::make('opens_at')->label('Dibuka Pada'),
            DateTimePicker::make('due_at')->label('Batas Pengumpulan'),
            TextInput::make('max_score')->label('Nilai Maksimal')->numeric()->default(100)->required(),
            Select::make('status')->label('Status')->options([
                'draft' => 'Draft',
                'published' => 'Terbit',
                'closed' => 'Ditutup',
            ])->default('published')->required(),
            Textarea::make('instructions')->label('Instruksi Tugas')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('module.courseClass.course.name')->label('Mata Kuliah')->searchable(),
                TextColumn::make('module.title')->label('Modul')->searchable(),
                TextColumn::make('title')->label('Tugas')->searchable(),
                TextColumn::make('opens_at')->label('Dibuka')->dateTime('d M Y H:i'),
                TextColumn::make('due_at')->label('Deadline')->dateTime('d M Y H:i'),
                TextColumn::make('submissions_count')->counts('submissions')->label('Submit'),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLmsAssignments::route('/'),
            'create' => Pages\CreateLmsAssignment::route('/create'),
            'edit' => Pages\EditLmsAssignment::route('/{record}/edit'),
        ];
    }
}
