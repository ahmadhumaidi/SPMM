<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Filament\Resources\LeadResource;
use App\Models\Lead;
use App\Services\StudentPaymentScheduleService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Tambah Pendaftar')
                ->after(function (Lead $record): void {
                    $payments = app(StudentPaymentScheduleService::class)->generateForLead($record);

                    if ($payments->isEmpty()) {
                        Notification::make()
                            ->title('Lead dibuat, tapi Rincian Biaya (RR) belum bisa dibuat otomatis')
                            ->body('Skema biaya untuk kombinasi kampus, program studi, dan program perkuliahan ini belum diatur. Atur skema biaya lalu generate RR manual dari halaman Rincian Biaya.')
                            ->warning()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title($payments->count().' baris Rincian Biaya (RR) berhasil dibuat otomatis')
                        ->success()
                        ->send();
                }),
        ];
    }
}
