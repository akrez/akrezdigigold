<?php

namespace App\Console\Commands;

use App\Enums\SourceEnum;
use App\Services\Scrapers\DigiKalaScraperService as ScrapersDigiKalaScraperService;
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

        $scraperService = match ($source) {
            SourceEnum::DIGIKALA->name => ScrapersDigiKalaScraperService::class,
            SourceEnum::SNAPSHOP->name => SnappShopScraperService::class,
            default => null,
        };
        if (! $scraperService) {
            $this->error('Invalid Source');

            return Command::FAILURE;
        }

        $status = app($scraperService)->analyze($scrapKey);
        if ($status > 200) {
            app(ScrapService::class)->clearSummaryCache();
        }
        $this->info('End');

        return Command::SUCCESS;
    }
}
