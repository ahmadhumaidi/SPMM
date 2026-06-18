<?php

namespace App\Filament\Pages;

use App\Models\Campus;
use App\Models\Lead;
use App\Support\FilamentResourceScope;
use Filament\Pages\Page;

class NewStudentRegistrationRecap extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Mahasiswa Baru';

    protected static ?string $navigationLabel = 'Rekap Pendaftaran';

    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.new-student-registration-recap';

    public ?string $campusId = '';

    public ?string $cohortYear = '';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessStudentRecords();
    }

    public function getCampusOptions(): array
    {
        return FilamentResourceScope::applyCampusScope(Campus::query()->orderBy('name'), 'id')
            ->pluck('name', 'id')
            ->all();
    }

    public function getCohortOptions(): array
    {
        return $this->baseLeadQuery()
            ->selectRaw('strftime("%Y", created_at) as registration_year')
            ->whereNotNull('created_at')
            ->distinct()
            ->pluck('registration_year')
            ->filter()
            ->map(fn ($year): string => (string) $year)
            ->unique()
            ->sortDesc()
            ->mapWithKeys(fn (string $year): array => [$year => $year])
            ->all();
    }

    public function getRecapRows(): array
    {
        $leads = $this->baseLeadQuery()->get();
        $tracks = $this->getTrackColumnsFromLeads($leads);
        $rows = [];

        foreach ($leads->groupBy('study_program_id') as $programLeads) {
            $firstLead = $programLeads->first();
            $row = $this->emptyRow(
                $firstLead?->studyProgram?->code ?: '-',
                $firstLead?->studyProgram?->name ?: 'Program Studi belum diisi',
                $tracks,
            );

            foreach ($programLeads as $lead) {
                $trackKey = $this->trackKeyFor($lead);
                $row['tracks'][$trackKey]['reg']++;

                if ($this->isHerregistered($lead)) {
                    $row['tracks'][$trackKey]['her']++;
                }
            }

            $row['total_reg'] = collect($tracks)->sum(fn (array $track): int => $row['tracks'][$track['key']]['reg']);
            $row['total_her'] = collect($tracks)->sum(fn (array $track): int => $row['tracks'][$track['key']]['her']);
            $row['percentage'] = $row['total_reg'] > 0 ? ($row['total_her'] / $row['total_reg']) * 100 : 0;

            $rows[] = $row;
        }

        usort($rows, fn (array $a, array $b): int => strnatcasecmp($a['name'], $b['name']));

        return $rows;
    }

    public function getTrackColumns(): array
    {
        return $this->getTrackColumnsFromLeads($this->baseLeadQuery()->get());
    }

    public function getTotals(array $rows): array
    {
        $tracks = $this->getTrackColumns();
        $totals = $this->emptyRow('TOTAL', 'Total Mahasiswa Keseluruhan', $tracks);

        foreach ($rows as $row) {
            foreach ($tracks as $track) {
                $totals['tracks'][$track['key']]['reg'] += $row['tracks'][$track['key']]['reg'] ?? 0;
                $totals['tracks'][$track['key']]['her'] += $row['tracks'][$track['key']]['her'] ?? 0;
            }

            $totals['total_reg'] += $row['total_reg'];
            $totals['total_her'] += $row['total_her'];
        }

        $totals['percentage'] = $totals['total_reg'] > 0 ? ($totals['total_her'] / $totals['total_reg']) * 100 : 0;

        return $totals;
    }

    protected function baseLeadQuery()
    {
        return FilamentResourceScope::applyManagedLeadCampusScope(Lead::query())
            ->with(['campus', 'studyProgram', 'classTrack', 'studentBiodata'])
            ->where(function ($query): void {
                $query->whereDoesntHave('studentBiodata')
                    ->orWhereHas('studentBiodata', fn ($biodataQuery) => $biodataQuery->where('student_type', 'new'));
            })
            ->when(filled($this->campusId), fn ($query) => $query->where('leads.campus_id', $this->campusId))
            ->when(filled($this->cohortYear), function ($query): void {
                $query->whereYear('created_at', $this->cohortYear);
            });
    }

    protected function emptyRow(string $code, string $name, array $tracks): array
    {
        return [
            'code' => $code,
            'name' => $name,
            'tracks' => collect($tracks)
                ->mapWithKeys(fn (array $track): array => [$track['key'] => ['reg' => 0, 'her' => 0]])
                ->all(),
            'total_reg' => 0,
            'total_her' => 0,
            'percentage' => 0,
        ];
    }

    protected function getTrackColumnsFromLeads($leads): array
    {
        $tracks = $leads
            ->map(fn (Lead $lead): array => [
                'key' => $this->trackKeyFor($lead),
                'label' => $lead->classTrack?->name ?: 'Belum Dipilih',
            ])
            ->unique('key')
            ->sortBy('label')
            ->values()
            ->all();

        if ($tracks === []) {
            return [
                ['key' => 'belum-dipilih', 'label' => 'Belum Dipilih'],
            ];
        }

        return $tracks;
    }

    protected function trackKeyFor(Lead $lead): string
    {
        return str($lead->classTrack?->name ?: 'Belum Dipilih')->slug()->toString();
    }

    protected function isHerregistered(Lead $lead): bool
    {
        return ($lead->payment_status?->value ?? $lead->payment_status) === 'paid';
    }
}
