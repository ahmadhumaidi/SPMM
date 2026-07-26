<?php

namespace App\Filament\Widgets;

use App\Enums\EnrollmentStatus;
use App\Models\AcademicTerm;
use App\Models\CourseClass;
use App\Models\Lead;
use App\Models\StudyPlan;
use App\Support\SiakadCampusResolver;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SiakadOverviewWidget extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $campus = SiakadCampusResolver::current();

        if (! $campus) {
            return [
                Stat::make('Mahasiswa Aktif', 0),
                Stat::make('Kelas Dibuka', 0),
                Stat::make('KRS Menunggu', 0),
            ];
        }

        $activeTerm = AcademicTerm::query()
            ->where('campus_id', $campus->id)
            ->where('status', 'active')
            ->orderByDesc('starts_at')
            ->first();

        $mahasiswaAktif = Lead::query()
            ->where('campus_id', $campus->id)
            ->where('enrollment_status', EnrollmentStatus::MahasiswaAktif)
            ->count();

        $kelasDibuka = $activeTerm
            ? CourseClass::query()->where('academic_term_id', $activeTerm->id)->where('status', 'open')->count()
            : 0;

        $krsMenunggu = $activeTerm
            ? StudyPlan::query()->where('academic_term_id', $activeTerm->id)->where('status', 'submitted')->count()
            : 0;

        return [
            Stat::make('Mahasiswa Aktif', $mahasiswaAktif)
                ->description($campus->name)
                ->color('danger'),
            Stat::make('Kelas Dibuka', $kelasDibuka)
                ->description($activeTerm?->name ?? 'Belum ada tahun akademik aktif')
                ->color('danger'),
            Stat::make('KRS Menunggu Persetujuan', $krsMenunggu)
                ->description('Status submitted')
                ->color('warning'),
        ];
    }
}
