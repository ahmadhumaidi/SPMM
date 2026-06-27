<?php

namespace App\Jobs;

use App\Services\MetaLeadImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ImportMetaFormLeadsJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(
        public readonly string $formId,
        public readonly int $limit = 100,
        public readonly ?int $requestedByUserId = null,
    ) {
        $this->onQueue('default');
    }

    public function handle(MetaLeadImportService $importer): void
    {
        $result = $importer->importForm($this->formId, $this->limit);

        Log::info('Meta lead form import finished.', [
            'form_id' => $this->formId,
            'limit' => $this->limit,
            'requested_by_user_id' => $this->requestedByUserId,
            'result' => $result,
        ]);
    }
}
