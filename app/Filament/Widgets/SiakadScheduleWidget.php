<?php

namespace App\Filament\Widgets;

use App\Models\AcademicTerm;
use App\Models\CourseClass;
use App\Support\SiakadCampusResolver;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class SiakadScheduleWidget extends TableWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $campus = SiakadCampusResolver::current();

        $activeTerm = $campus
            ? AcademicTerm::query()->where('campus_id', $campus->id)->where('status', 'active')->orderByDesc('starts_at')->first()
            : null;

        return $table
            ->heading('Jadwal Kelas Semester Ini')
            ->query(
                CourseClass::query()->when(
                    $activeTerm,
                    fn (Builder $query) => $query->where('academic_term_id', $activeTerm->id),
                    fn (Builder $query) => $query->whereRaw('1 = 0'),
                )
            )
            ->columns([
                TextColumn::make('course.code')->label('Kode')->searchable(),
                TextColumn::make('course.name')->label('Mata Kuliah')->searchable(),
                TextColumn::make('class_name')->label('Kelas'),
                TextColumn::make('lecturer.name')->label('Dosen')->searchable(),
                TextColumn::make('day_of_week')->label('Hari'),
                TextColumn::make('starts_at')->label('Mulai'),
                TextColumn::make('ends_at')->label('Selesai'),
                TextColumn::make('room')->label('Ruang'),
                TextColumn::make('status')->label('Status')->badge(),
            ])
            ->paginated(false);
    }
}
