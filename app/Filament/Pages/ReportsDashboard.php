<?php

namespace App\Filament\Pages;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Lead;
use App\Support\FilamentResourceScope;
use Filament\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class ReportsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Ringkasan MVP';

    protected static ?string $title = 'Ringkasan MVP';

    protected static string $view = 'filament.pages.reports-dashboard';

    public static function canAccess(): bool
    {
        return FilamentResourceScope::canAccessReports();
    }

    public function getLeadByCampus(): Collection
    {
        return $this->leadQuery()
            ->selectRaw('campuses.name as label, count(*) as total')
            ->join('campuses', 'campuses.id', '=', 'leads.campus_id')
            ->groupBy('campuses.name')
            ->orderByDesc('total')
            ->get();
    }

    public function getLeadByStatus(): Collection
    {
        return $this->leadQuery()
            ->selectRaw('lead_status as label, count(*) as total')
            ->groupBy('lead_status')
            ->orderByDesc('total')
            ->get();
    }

    public function getLeadByStudyProgram(): Collection
    {
        return $this->leadQuery()
            ->selectRaw('study_programs.name as label, count(*) as total')
            ->join('study_programs', 'study_programs.id', '=', 'leads.study_program_id')
            ->groupBy('study_programs.name')
            ->orderByDesc('total')
            ->limit(10)
            ->get();
    }

    public function getPaymentSummary(): array
    {
        return [
            'pending' => $this->invoiceQuery()->where('status', InvoiceStatus::Pending)->count(),
            'paid' => $this->invoiceQuery()->where('status', InvoiceStatus::Paid)->count(),
            'expired' => $this->invoiceQuery()->where('status', InvoiceStatus::Expired)->count(),
            'paid_amount' => $this->invoiceQuery()->where('status', InvoiceStatus::Paid)->sum('amount'),
        ];
    }

    protected function leadQuery(): Builder
    {
        return FilamentResourceScope::applyManagedLeadCampusScope(Lead::query());
    }

    protected function invoiceQuery(): Builder
    {
        return Invoice::query()
            ->whereHas('lead', fn (Builder $leadQuery): Builder => FilamentResourceScope::applyManagedLeadCampusScope($leadQuery));
    }
}
