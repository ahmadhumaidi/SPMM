<?php

namespace App\Jobs;

use App\Services\MetaLeadImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class ImportMetaFormLeadsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(
        public readonly string $formId,
        public readonly int $limit = 100,
        public readonly ?int $requestedByUserId = null,
        public readonly bool $reprocessExisting = false,
    ) {
        $this->onQueue('default');
    }

    public function handle(MetaLeadImportService $importer): void
    {
        try {
            $result = $importer->importForm($this->formId, $this->limit, $this->reprocessExisting);
        } catch (Throwable $exception) {
            Log::warning('Meta lead form import skipped or failed.', [
                'form_id' => $this->formId,
                'limit' => $this->limit,
                'requested_by_user_id' => $this->requestedByUserId,
                'reprocess_existing' => $this->reprocessExisting,
                'error' => $exception->getMessage(),
            ]);

            return;
        }

        Log::info('Meta lead form import finished.', [
            'form_id' => $this->formId,
            'limit' => $this->limit,
            'requested_by_user_id' => $this->requestedByUserId,
            'reprocess_existing' => $this->reprocessExisting,
            'result' => $result,
        ]);
    }
}
