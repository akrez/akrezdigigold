<?php

namespace App\Services;

use App\Enums\CaratEnum;
use App\Enums\SourceEnum;
use App\Models\Scrap;
use App\Models\Variant;
use Illuminate\Support\Facades\Cache;

class ScrapService
{
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

    public function buildShortSummaryCache(int $length = 10): array
    {
        return Cache::remember($this->getCacheKey(self::CACHE_KEY_SECTION_SHORT_SUMMARY), self::CACHE_TTL, function () use ($length) {
            $summary = $this->buildSummaryCache();
            foreach ($summary['scraps'] as $scrapKey => $scrap) {
                foreach ($scrap['variants'] as $carat => $variants) {
                    $summary['scraps'][$scrapKey]['variants'][$carat] = array_slice($variants, 0, $length);
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
            ];
            foreach (SourceEnum::cases() as $sourceEnum) {
                $scrap = $this->buildScrapSummary($sourceEnum, self::CACHE_TTL);
                if ($scrap) {
                    $result['scraps'][] = $scrap;
                }
            }

            return $result;
        });
    }

    protected function buildScrapSummary(SourceEnum $sourceEnum, int $createdAtSecondsAgo): array
    {
        $scrap = Scrap::query()
            ->where('source', $sourceEnum->name)
            ->whereTime('created_at', '>=', now()->subSeconds($createdAtSecondsAgo))
            ->whereNotNull('completed_at')
            ->orderBy('completed_at', 'DESC')
            ->first();
        if (! $scrap) {
            return [];
        }

        $result = [
            'source' => $sourceEnum->resource(),
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
