<?php

namespace App\Filament\Pages;

use App\Models\ReferralConversion;
use App\Models\StudentPayment;
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
        $transaction = $this->studentPaymentQuery()->count();
        $income = (int) $this->studentPaymentQuery()->sum('amount');
        $expense = $this->paidReferralCommissionAmount();

        return [
            'transaction' => $transaction,
            'income' => $income,
            'expense' => $expense,
            'total_funding' => $income - $expense,
        ];
    }

    protected function leadQuery(): Builder
    {
        return FilamentResourceScope::applyManagedLeadCampusScope(Lead::query());
    }

    protected function studentPaymentQuery(): Builder
    {
        return StudentPayment::query()
            ->where('status', 'paid')
            ->where(fn (Builder $query): Builder => $query->whereNull('payment_type')->orWhere('payment_type', '!=', 'manual'))
            ->whereHas('lead', fn (Builder $leadQuery): Builder => FilamentResourceScope::applyManagedLeadCampusScope($leadQuery));
    }

    protected function referralConversionQuery(): Builder
    {
        return ReferralConversion::query()
            ->whereHas('lead', fn (Builder $leadQuery): Builder => FilamentResourceScope::applyManagedLeadCampusScope($leadQuery));
    }

    protected function paidReferralCommissionAmount(): int
    {
        $conversions = $this->referralConversionQuery()
            ->get([
                'registration_commission_amount',
                'registration_commission_status',
                'herregistration_commission_amount',
                'herregistration_commission_status',
                'semester1_commission_amount',
                'semester1_commission_status',
            ]);

        return (int) $conversions->sum(function (ReferralConversion $conversion): int {
            return (int) ($conversion->registration_commission_status === 'paid' ? $conversion->registration_commission_amount : 0)
                + (int) ($conversion->herregistration_commission_status === 'paid' ? $conversion->herregistration_commission_amount : 0)
                + (int) ($conversion->semester1_commission_status === 'paid' ? $conversion->semester1_commission_amount : 0);
        });
    }
}
