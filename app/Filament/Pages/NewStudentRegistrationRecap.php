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
            ->selectRaw($this->registrationYearSelect())
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

    protected function registrationYearSelect(): string
    {
        return match (Lead::query()->getConnection()->getDriverName()) {
            'pgsql' => 'EXTRACT(YEAR FROM created_at)::int as registration_year',
            'sqlite' => 'strftime("%Y", created_at) as registration_year',
            default => 'YEAR(created_at) as registration_year',
        };
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
                $row['tracks'][$trackKey]['lead']++;

                if ($this->isRegistered($lead)) {
                    $row['tracks'][$trackKey]['registration']++;
                }

                if ($this->isHerregistered($lead)) {
                    $row['tracks'][$trackKey]['herregistration']++;
                }
            }

            $row['total_lead'] = collect($tracks)->sum(fn (array $track): int => $row['tracks'][$track['key']]['lead']);
            $row['total_registration'] = collect($tracks)->sum(fn (array $track): int => $row['tracks'][$track['key']]['registration']);
            $row['total_herregistration'] = collect($tracks)->sum(fn (array $track): int => $row['tracks'][$track['key']]['herregistration']);
            $row['registration_percentage'] = $row['total_lead'] > 0 ? ($row['total_registration'] / $row['total_lead']) * 100 : 0;
            $row['herregistration_percentage'] = $row['total_registration'] > 0 ? ($row['total_herregistration'] / $row['total_registration']) * 100 : 0;

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
                $totals['tracks'][$track['key']]['lead'] += $row['tracks'][$track['key']]['lead'] ?? 0;
                $totals['tracks'][$track['key']]['registration'] += $row['tracks'][$track['key']]['registration'] ?? 0;
                $totals['tracks'][$track['key']]['herregistration'] += $row['tracks'][$track['key']]['herregistration'] ?? 0;
            }

            $totals['total_lead'] += $row['total_lead'];
            $totals['total_registration'] += $row['total_registration'];
            $totals['total_herregistration'] += $row['total_herregistration'];
        }

        $totals['registration_percentage'] = $totals['total_lead'] > 0 ? ($totals['total_registration'] / $totals['total_lead']) * 100 : 0;
        $totals['herregistration_percentage'] = $totals['total_registration'] > 0 ? ($totals['total_herregistration'] / $totals['total_registration']) * 100 : 0;

        return $totals;
    }

    protected function baseLeadQuery()
    {
        return FilamentResourceScope::applyManagedLeadCampusScope(Lead::query())
            ->with(['campus', 'studyProgram', 'classTrack', 'studentBiodata', 'studentPayments'])
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
                ->mapWithKeys(fn (array $track): array => [$track['key'] => [
                    'lead' => 0,
                    'registration' => 0,
                    'herregistration' => 0,
                ]])
                ->all(),
            'total_lead' => 0,
            'total_registration' => 0,
            'total_herregistration' => 0,
            'registration_percentage' => 0,
            'herregistration_percentage' => 0,
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

    protected function isRegistered(Lead $lead): bool
    {
        $paidStatuses = ['paid', 'waived'];

        return ($lead->payment_status?->value ?? $lead->payment_status) === 'paid'
            || $lead->studentPayments->contains(fn ($payment): bool => (int) $payment->month === 0
                && in_array($payment->status, $paidStatuses, true));
    }

    protected function isHerregistered(Lead $lead): bool
    {
        $paidStatuses = ['paid', 'waived'];

        return $lead->studentPayments->contains(fn ($payment): bool => (int) $payment->month === 1
            && in_array($payment->status, $paidStatuses, true));
    }
}
