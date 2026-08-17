<?php

namespace App\Filament\Resources\LeadResource\Pages;

use App\Exports\LeadsTemplateExport;
use App\Filament\Resources\LeadResource;
use App\Imports\LeadsImport;
use App\Models\Lead;
use App\Services\AuditLogger;
use App\Services\StudentPaymentScheduleService;
use App\Support\FilamentResourceScope;
use Filament\Actions;
use Filament\Forms\Components\Actions as FormActions;
use Filament\Forms\Components\Actions\Action as FormAction;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ListLeads extends ListRecords
{
    protected static string $resource = LeadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('import_manual')
                ->label('Import Manual')
                ->icon('heroicon-o-document-arrow-up')
                ->modalHeading('Import Manual Lead dari Excel')
                ->modalDescription('Download template terlebih dahulu jika belum punya, lalu upload file yang sudah diisi.')
                ->form([
                    FormActions::make([
                        FormAction::make('download_lead_template')
                            ->label('Download Template')
                            ->icon('heroicon-o-arrow-down-tray')
                            ->color('gray')
                            ->action(fn () => Excel::download(new LeadsTemplateExport(static::accessibleCampusIds()), 'template-import-manual-lead.xlsx')),
                    ])->alignEnd(),
                    FileUpload::make('file')
                        ->label('File Excel (xlsx/xls)')
                        ->helperText('Gunakan template yang sudah didownload. Isi Kampus, Program Studi, dan Program Perkuliahan persis seperti di sheet "Daftar Kampus & Program".')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-excel',
                        ])
                        ->disk('local')
                        ->directory('lead-imports')
                        ->visibility('private')
                        ->required(),
                ])
                ->action(function (array $data, AuditLogger $audit): void {
                    $path = Storage::disk('local')->path($data['file']);

                    $import = new LeadsImport(static::accessibleCampusIds());
                    Excel::import($import, $path);

                    Storage::disk('local')->delete($data['file']);

                    foreach ($import->createdLeads as $lead) {
                        $audit->record('lead_created', $lead, ['source' => 'manual_import']);
                    }

                    $createdCount = count($import->createdLeads);
                    $failures = $import->failures();

                    if ($failures->isNotEmpty()) {
                        Notification::make()
                            ->title("Berhasil tambah {$createdCount} lead, {$failures->count()} baris dilewati")
                            ->body(
                                "Baris dilewati karena data tidak valid atau Kampus/Program Studi/Program Perkuliahan tidak cocok:\n".
                                $failures
                                    ->map(fn ($failure) => 'Baris '.$failure->row().': '.implode(', ', $failure->errors()))
                                    ->implode("\n")
                            )
                            ->warning()
                            ->persistent()
                            ->send();
                    } else {
                        Notification::make()
                            ->title("Berhasil tambah {$createdCount} lead")
                            ->success()
                            ->send();
                    }
                }),
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

    /**
     * @return array<int, int>|null Null means unrestricted (super admin / direktur).
     */
    protected static function accessibleCampusIds(): ?array
    {
        if (FilamentResourceScope::isSuperAdmin() || FilamentResourceScope::isDirector()) {
            return null;
        }

        return auth()->user()?->campuses()->pluck('campuses.id')->all() ?? [];
    }
}
