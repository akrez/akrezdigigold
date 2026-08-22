<?php

namespace App\Console\Commands;

use App\Services\ScrapService;
use Illuminate\Console\Command;

class ScrapSummarizeCommand extends Command
{
    protected $signature = 'scrap:summarize';

    protected $description = 'scrap summarize';

    public function handle(): int
    {
        app(ScrapService::class)->buildSummaryCache();
        $this->info('End');

        return Command::SUCCESS;
    }
}
