<?php

namespace App\Listeners;

use App\Enums\CaratEnum;
use App\Events\ScrapAnalyzed;
use App\Services\BaleService;
use App\Services\ScrapService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyScrapStatus implements ShouldQueue
{
    public function __construct(
        protected ScrapService $scrapService,
        protected BaleService $bale,
    ) {}

    public function handle(ScrapAnalyzed $event): void
    {
        $summary = $this->scrapService->buildShortSummaryCache();
        $scrapSummary = $this->extractScrapSource($summary, $event->scrap->source);
        if (! $scrapSummary) {
            return;
        }
        $variant = $scrapSummary['variants'][CaratEnum::CARAT_18->name][0] ?? null;
        if (! $variant) {
            return;
        }
        $caption = [
            '*'.$variant['ttl'].'*',
            'بهترین قیمت سکه و شمش طلای '.$scrapSummary['source']['trans'].' تا ساعت '. verta()->format('H:i') .' تاریخ '.verta()->format('d %B Y'),
            '*عیار*' . ' ' . CaratEnum::CARAT_18->trans(),
            '*وزن*' . ' ' . $variant['siz'].' '.'گرم',
            '*قیمت*' . ' ' . $variant['prcf'],
            '*قیمت هر گرم*' . ' ' . $variant['ppgf'],
            '',
            $variant['url'],
            '',
            '#SCRAP_'.$scrapSummary['source']['name'],
            '',
            $this->bale->getChannelId(),
        ];

        $this->bale->sendPhoto($variant['img'], implode("\n", $caption), [
            'parse_mode' => 'Markdown',
        ]);
    }

    protected function extractScrapSource(array &$summary, string $sourceEnumName): ?array
    {
        foreach ($summary['scraps'] as $scrap) {
            if ($scrap['source']['name'] === $sourceEnumName) {
                return $scrap;
            }
        }

        return null;
    }
}
