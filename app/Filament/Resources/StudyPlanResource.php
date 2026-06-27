<?php

namespace App\Filament\Resources;

use App\Filament\Resources\StudyPlanResource\Pages;
use App\Models\CourseClass;
use App\Models\Lead;
use App\Models\StudyPlan;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class StudyPlanResource extends Resource
{
    protected static ?string $model = StudyPlan::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'SIAKAD';

    protected static ?string $navigationLabel = 'KRS & Nilai';

    protected static bool $shouldRegisterNavigation = false;

    public static function canAccess(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('lead_id')
                ->label('Mahasiswa')
                ->options(fn (): array => Lead::query()->orderBy('full_name')->pluck('full_name', 'id')->all())
                ->searchable()
                ->required(),
            Select::make('academic_term_id')->label('Tahun Akademik')->relationship('academicTerm', 'name')->searchable()->preload()->required(),
            Select::make('status')->label('Status KRS')->options([
                'draft' => 'Draft',
                'submitted' => 'Diajukan',
                'approved' => 'Disetujui',
                'rejected' => 'Ditolak',
            ])->default('draft')->required(),
            DateTimePicker::make('submitted_at')->label('Tanggal ajukan'),
            DateTimePicker::make('approved_at')->label('Tanggal setujui'),
            Textarea::make('notes')->label('Catatan')->columnSpanFull(),
            Repeater::make('items')
                ->label('Mata Kuliah KRS')
                ->relationship()
                ->schema([
                    Select::make('course_class_id')
                        ->label('Kelas Mata Kuliah')
                        ->options(fn (): array => CourseClass::query()->with('course')->get()->mapWithKeys(fn (CourseClass $class): array => [
                            $class->id => $class->course?->code.' - '.$class->course?->name.' / Kelas '.$class->class_name,
                        ])->all())
                        ->searchable()
                        ->required(),
                    Select::make('status')->label('Status')->options(['taken' => 'Diambil', 'dropped' => 'Batal'])->default('taken'),
                    TextInput::make('assignment_score')->label('Tugas')->numeric(),
                    TextInput::make('midterm_score')->label('UTS')->numeric(),
                    TextInput::make('final_score')->label('UAS')->numeric(),
                    TextInput::make('final_numeric_score')->label('Nilai Angka')->numeric(),
                    TextInput::make('final_grade')->label('Huruf')->maxLength(4),
                    TextInput::make('grade_point')->label('Bobot')->numeric(),
                ])
                ->columns(4)
                ->columnSpanFull(),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \App\Support\FilamentTable::rowNumberColumn(),
                TextColumn::make('lead.full_name')->label('Mahasiswa')->searchable(),
                TextColumn::make('lead.campus.name')->label('Kampus'),
                TextColumn::make('academicTerm.name')->label('Periode')->searchable(),
                TextColumn::make('status')->label('Status')->badge(),
                TextColumn::make('items_count')->counts('items')->label('MK'),
                TextColumn::make('submitted_at')->label('Diajukan')->dateTime('d M Y H:i'),
                TextColumn::make('approved_at')->label('Disetujui')->dateTime('d M Y H:i'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStudyPlans::route('/'),
            'create' => Pages\CreateStudyPlan::route('/create'),
            'edit' => Pages\EditStudyPlan::route('/{record}/edit'),
        ];
    }
}
