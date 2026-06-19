<?php

namespace App\Console\Commands;

use App\Services\AiEducationNewsDraftService;
use Illuminate\Console\Command;
use Throwable;

class GenerateAiEducationNewsDraft extends Command
{
    protected $signature = 'spmm:news:generate-ai-draft {--topic= : Topik khusus untuk pencarian berita}';

    protected $description = 'Generate satu draft berita pendidikan dari RSS dan AI jika API key tersedia.';

    public function handle(AiEducationNewsDraftService $service): int
    {
        try {
            $news = $service->createDraft(topic: $this->option('topic'));
        } catch (Throwable $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Draft dibuat: {$news->title}");

        return self::SUCCESS;
    }
}
