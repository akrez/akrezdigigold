<?php

namespace App\Console\Commands;

use App\Enums\SourceEnum;
use App\Services\Scrapers\DigiKalaScraperService;
use App\Services\Scrapers\SnappShopScraperService;
use App\Services\ScrapService;
use Illuminate\Console\Command;

class ScrapAnalyzeCommand extends Command
{
    protected $signature = 'scrap:analyze {source : Source to scrape} {--key= : Scrap key (default: YmdH0000)}';

    protected $description = 'scrap analyze';

    public function handle(): int
    {
        $source = $this->argument('source') ?? null;
        $source = strtoupper($source);

        $scrapKey = $this->option('key') ?? now()->format('YmdH0000');

        $scraperServiceClassName = match ($source) {
            SourceEnum::DIGIKALA->name => DigiKalaScraperService::class,
            SourceEnum::SNAPSHOP->name => SnappShopScraperService::class,
            default => null,
        };
        if (! $scraperServiceClassName) {
            $this->error('Invalid Source');

            return Command::FAILURE;
        }

        $scraperService = app($scraperServiceClassName);

        $scrap = $scraperService->firstOrCreateScrap($scrapKey);
        if (! $scrap) {
            return Command::FAILURE;
        }

        $status = $scraperService->analyze($scrap);
        if ($status > 200) {
            app(ScrapService::class)->forgetSummaryCache();
        }
        $this->info('End');

        return Command::SUCCESS;
    }
}
