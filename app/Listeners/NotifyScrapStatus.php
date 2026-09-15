<?php

namespace App\Listeners;

use App\Enums\CaratEnum;
use App\Events\ScrapAnalyzed;
use App\Services\BaleService;
use App\Services\ScrapService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

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
        $hashtag = '#SCRAP_'.$scrapSummary['source']['name'];
        $this->deleteMessages($hashtag);
        $caption = [
            '*'.$variant['ttl'].'*',
            implode(' ', [
                'بهترین قیمت سکه و شمش طلای',
                '*'.$scrapSummary['source']['trans'].'*',
                'تا ساعت',
                verta()->format('H:i'),
                'تاریخ',
                verta()->format('d %B Y'),
            ]),
            '*عیار*'.' '.CaratEnum::CARAT_18->trans(),
            '*وزن*'.' '.$variant['siz'].' '.'گرم',
            '*قیمت*'.' '.$variant['prcf'],
            '*قیمت هر گرم*'.' '.$variant['ppgf'],
            '',
            $variant['url'],
            '',
            $hashtag,
            '',
            $this->bale->getChannelId(),
        ];

        $this->bale->sendPhoto($variant['img'], implode("\n", $caption), [
            'parse_mode' => 'Markdown',
        ]);
    }

    protected function deleteMessages(string $filter)
    {
        $updates = $this->bale->getUpdates(24)->json('result');
        foreach ($updates as $update) {
            if (
                Str::contains(Arr::get($update, 'message.text'), $filter) ||
                Str::contains(Arr::get($update, 'message.caption'), $filter)
            ) {
                $messageId = Arr::get($update, 'message.message_id');
                $chatId = Arr::get($update, 'message.chat.id');
                if ($chatId && $messageId) {
                    $this->bale->deleteMessage($chatId, $messageId);
                }
            }
        }
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
