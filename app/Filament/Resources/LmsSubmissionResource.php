<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LmsSubmissionResource\Pages;
use App\Models\Lead;
use App\Models\LmsAssignment;
use App\Models\LmsSubmission;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LmsSubmissionResource extends Resource
{
    protected static ?string $model = LmsSubmission::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $navigationGroup = 'LMS Kampus';

    protected static ?string $navigationLabel = 'Pengumpulan Tugas';

    protected static ?int $navigationSort = 4;

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('lms_assignment_id')
                ->label('Tugas')
                ->options(fn (): array => LmsAssignment::query()
                    ->with('module.courseClass.course')
                    ->get()
                    ->mapWithKeys(fn (LmsAssignment $assignment): array => [
                        $assignment->id => ($assignment->module?->courseClass?->course?->name ?? 'Mata kuliah').' / '.$assignment->title,
                    ])
                    ->all())
                ->searchable()
                ->required(),
            Select::make('lead_id')
                ->label('Mahasiswa')
                ->options(fn (): array => Lead::query()->orderBy('full_name')->pluck('full_name', 'id')->all())
                ->searchable()
                ->required(),
            DateTimePicker::make('submitted_at')->label('Waktu Submit'),
            Select::make('status')->label('Status')->options([
                'submitted' => 'Terkumpul',
                'late' => 'Terlambat',
                'graded' => 'Sudah Dinilai',
                'returned' => 'Dikembalikan',
            ])->default('submitted')->required(),
            FileUpload::make('file_path')->label('File Jawaban')->directory('lms/submissions')->downloadable()->openable(),
            TextInput::make('score')->label('Nilai')->numeric(),
            Textarea::make('answer_text')->label('Jawaban Teks')->columnSpanFull(),
            Textarea::make('feedback')->label('Feedback Dosen/Admin')->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('assignment.module.courseClass.course.name')->label('Mata Kuliah')->searchable(),
                TextColumn::make('assignment.title')->label('Tugas')->searchable(),
                TextColumn::make('lead.full_name')->label('Mahasiswa')->searchable(),
                TextColumn::make('submitted_at')->label('Submit')->dateTime('d M Y H:i'),
                TextColumn::make('score')->label('Nilai'),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLmsSubmissions::route('/'),
            'create' => Pages\CreateLmsSubmission::route('/create'),
            'edit' => Pages\EditLmsSubmission::route('/{record}/edit'),
        ];
    }
}
