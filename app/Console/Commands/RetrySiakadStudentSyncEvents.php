<?php

namespace App\Console\Commands;

use App\Models\SiakadStudentSyncEvent;
use App\Services\SiakadStudentSyncService;
use Illuminate\Console\Command;

class RetrySiakadStudentSyncEvents extends Command
{
    protected $signature = 'spmm:siakad-sync:retry {--limit=50 : Maksimal event yang dikirim ulang}';

    protected $description = 'Kirim ulang event aktivasi mahasiswa ke SIAKAD yang belum berhasil.';

    public function handle(SiakadStudentSyncService $siakadSync): int
    {
        if (blank(config('spmm.siakad_integration.base_url')) || blank(config('spmm.siakad_integration.api_token'))) {
            $this->warn('SIAKAD_INTEGRATION_BASE_URL atau SIAKAD_INTEGRATION_API_TOKEN belum diisi. Retry dilewati.');

            return self::SUCCESS;
        }

        $limit = max(1, min((int) $this->option('limit'), 500));
        $events = SiakadStudentSyncEvent::query()
            ->whereIn('status', ['pending', 'pending_config', 'failed'])
            ->oldest()
            ->limit($limit)
            ->get();

        foreach ($events as $event) {
            $event->update(['status' => 'pending', 'error_message' => null]);
            $siakadSync->send($event->fresh());
        }

        $this->info("Retry {$events->count()} event sinkronisasi SIAKAD selesai.");

        return self::SUCCESS;
    }
}
