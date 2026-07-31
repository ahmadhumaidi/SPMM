<?php

namespace App\Filament\Widgets;

use App\Enums\EnrollmentStatus;
use App\Models\AcademicTerm;
use App\Models\Lead;
use App\Support\SiakadCampusResolver;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use Illuminate\Database\Eloquent\Builder;

class SiakadPendingKrsWidget extends TableWidget
{
    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $campus = SiakadCampusResolver::current();

        $activeTerm = $campus
            ? AcademicTerm::query()->where('campus_id', $campus->id)->where('status', 'active')->orderByDesc('starts_at')->first()
            : null;

        return $table
            ->heading('Mahasiswa Aktif Belum Mengisi KRS')
            ->query(
                Lead::query()
                    ->when(
                        $campus && $activeTerm,
                        fn (Builder $query) => $query
                            ->where('campus_id', $campus->id)
                            ->where('enrollment_status', EnrollmentStatus::MahasiswaAktif)
                            ->whereDoesntHave('studyPlans', fn (Builder $q) => $q->where('academic_term_id', $activeTerm->id)),
                        fn (Builder $query) => $query->whereRaw('1 = 0'),
                    )
            )
            ->columns([
                TextColumn::make('full_name')->label('Mahasiswa')->searchable(),
                TextColumn::make('studyProgram.name')->label('Program Studi'),
            ])
            ->defaultPaginationPageOption(5);
    }
}
