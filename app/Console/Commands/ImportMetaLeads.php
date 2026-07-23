<?php

namespace App\Console\Commands;

use App\Jobs\ImportMetaFormLeadsJob;
use Illuminate\Console\Command;

class ImportMetaLeads extends Command
{
    protected $signature = 'spmm:meta-leads:import {--form-id= : Form ID Meta Lead Ads} {--limit= : Batas jumlah lead yang dicek} {--reprocess-existing : Proses ulang lead yang sudah pernah masuk}';

    protected $description = 'Import lead Meta dari form yang dikonfigurasi sebagai cadangan webhook otomatis.';

    public function handle(): int
    {
        $formId = (string) ($this->option('form-id') ?: config('spmm.meta_leads.form_id'));

        if ($formId === '') {
            $this->warn('META_LEADS_FORM_ID belum diisi. Import Meta Lead dilewati.');

            return self::SUCCESS;
        }

        $limit = (int) ($this->option('limit') ?: config('spmm.meta_leads.auto_import_limit', 100));
        $limit = max(1, min($limit, 1000));

        ImportMetaFormLeadsJob::dispatch(
            $formId,
            $limit,
            null,
            (bool) $this->option('reprocess-existing'),
        );

        $this->info("Import Meta Lead dijadwalkan untuk form {$formId} dengan limit {$limit}.");

        return self::SUCCESS;
    }
}
