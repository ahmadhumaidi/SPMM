<?php

namespace App\Console\Commands;

use App\Models\LeadProspectEvent;
use App\Services\MetaProspectEventService;
use Illuminate\Console\Command;

class RetryMetaProspectEvents extends Command
{
    protected $signature = 'spmm:meta-prospect-events:retry {--limit=50 : Maksimal event yang dikirim ulang}';

    protected $description = 'Kirim ulang event status prospek ke Meta Conversions API.';

    public function handle(MetaProspectEventService $metaEvents): int
    {
        if (blank(config('spmm.meta_conversions.pixel_id')) || blank(config('spmm.meta_conversions.access_token'))) {
            $this->warn('META_CAPI_PIXEL_ID atau META_CAPI_ACCESS_TOKEN belum diisi. Retry dilewati.');

            return self::SUCCESS;
        }

        $limit = max(1, min((int) $this->option('limit'), 500));
        $events = LeadProspectEvent::query()
            ->whereIn('meta_status', ['pending', 'pending_config', 'failed'])
            ->oldest()
            ->limit($limit)
            ->get();

        foreach ($events as $event) {
            $event->update(['meta_status' => 'pending', 'meta_error_message' => null]);
            $metaEvents->send($event->fresh());
        }

        $this->info("Retry {$events->count()} event prospek Meta selesai.");

        return self::SUCCESS;
    }
}
