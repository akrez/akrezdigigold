<?php

namespace App\Services;

use App\Enums\CaratEnum;
use App\Enums\SourceEnum;
use App\Models\Scrap;
use App\Models\Variant;
use Illuminate\Support\Facades\Cache;
use Lang;

class ScrapService extends Service
{
    const SHORT_SUMMARY_LENGTH = 10;

    const CACHE_TTL = 7200;

    const CACHE_KEY_SECTION_SUMMARY = 'summary';

    const CACHE_KEY_SECTION_SHORT_SUMMARY = 'short_summary';

    protected function getCacheKey(?string $section): string
    {
        return implode('::', [__CLASS__, __FUNCTION__, $section]);
    }

    public function forgetCache(): void
    {
        Cache::forget($this->getCacheKey(self::CACHE_KEY_SECTION_SUMMARY));
        Cache::forget($this->getCacheKey(self::CACHE_KEY_SECTION_SHORT_SUMMARY));
    }

    public function buildShortSummaryCache(): array
    {
        return Cache::remember($this->getCacheKey(self::CACHE_KEY_SECTION_SHORT_SUMMARY), self::CACHE_TTL, function () {
            $summary = $this->buildSummaryCache();
            foreach ($summary['scraps'] as $scrapKey => $scrap) {
                foreach ($scrap['variants'] as $carat => $variants) {
                    $summary['scraps'][$scrapKey]['variants'][$carat] = array_slice((array) $variants, 0, self::SHORT_SUMMARY_LENGTH);
                }
            }

            return $summary;
        });
    }

    public function buildSummaryCache(): array
    {
        return Cache::remember($this->getCacheKey(self::CACHE_KEY_SECTION_SUMMARY), self::CACHE_TTL, function () {
            $result = [
                'carats' => CaratEnum::collection(),
                'scraps' => [],
                'categories' => [],
                'sources' => [],
            ];

            $latestScraps = Scrap::query()
                ->select('source', 'category')
                ->selectRaw('MAX(id) as id')
                ->whereNotNull('completed_at')
                ->where('created_at', '>=', now()->subSeconds(self::CACHE_TTL))
                ->groupBy('source', 'category')
                ->get();
            foreach ($latestScraps as $scrap) {
                $result['categories'][$scrap->category] = [
                    'name' => $scrap->category,
                    'trans' => Lang::has('categories.'.$scrap->category) ? __('categories.'.$scrap->category) : $scrap->category,
                ];
                $result['sources'][$scrap->source] = SourceEnum::fromString($scrap->source)->resource();
            }
            $result['categories'] = array_values($result['categories']);
            $result['sources'] = array_values($result['sources']);

            $scraps = Scrap::query()
                ->whereIn('id', $latestScraps->pluck('id'))
                ->get();

            foreach ($scraps as $scrap) {
                $scrap = $this->buildScrapSummary($scrap);
                if ($scrap) {
                    $result['scraps'][] = $scrap;
                }
            }

            return $result;
        });
    }

    protected function buildScrapSummary(Scrap $scrap): array
    {
        $result = [
            'source' => $scrap->source,
            'category' => $scrap->category,
            'completed_at' => $scrap->completed_at,
            'variants' => array_fill_keys(CaratEnum::names(), null),
        ];

        $variants = Variant::query()
            ->where('scrap_id', $scrap->id)
            ->with(['product'])
            ->whereNotNull('carat')
            ->where('size', '>', 0)
            ->where('price_per_gram', '>', 0)
            ->orderBy('price_per_gram')
            ->get();

        $variants->each(function ($variant) use (&$result) {
            $price = intval($variant->price);
            $pricePerGram = intval($price / $variant->size);
            $result['variants'][$variant->carat][] = [
                'id' => $variant->id,
                //
                'ttl' => $variant->product->title,
                'siz' => $variant->size,
                'url' => $variant->product->product_url,
                'img' => $variant->product->image_url,
                'sel' => $variant->seller,
                'src' => $variant->scrap->source,
                //
                'prcr' => $price,
                'prcf' => number_format(intval($price / 10)),
                //
                'ppgr' => $pricePerGram,
                'ppgf' => number_format(intval($pricePerGram / 10)),
            ];
        });

        return $result;
    }
}
