<?php

namespace App\Filament\Widgets;

use App\Enums\EnrollmentStatus;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\Lead;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class MvpOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total lead', Lead::query()->count())
                ->description('Semua kanal pendaftaran')
                ->color('info'),
            Stat::make('Invoice paid', Invoice::query()->where('status', InvoiceStatus::Paid)->count())
                ->description('Pembayaran sukses')
                ->color('success'),
            Stat::make('Menunggu pemutakhiran', Lead::query()->where('enrollment_status', EnrollmentStatus::MenungguPemutakhiran)->count())
                ->description('Siap dicek Super Admin')
                ->color('warning'),
            Stat::make('Mahasiswa aktif', Lead::query()->where('enrollment_status', EnrollmentStatus::MahasiswaAktif)->count())
                ->description('Sudah punya NIM')
                ->color('success'),
        ];
    }
}
