<?php

namespace App\Services;

use App\Enums\CaratEnum;
use App\Enums\SourceEnum;
use App\Models\Scrap;
use App\Models\Variant;
use Illuminate\Support\Facades\Cache;

class ScrapService
{
    protected function getSummaryCacheKey(): string
    {
        return implode('::', [__CLASS__, __FUNCTION__]);
    }

    public function forgetSummaryCache(): bool
    {
        return Cache::forget($this->getSummaryCacheKey());
    }

    public function buildSummaryCache(int $ttl = 3600): array
    {
        return Cache::remember($this->getSummaryCacheKey(), $ttl, function () use ($ttl) {
            $result = [
                'carats' => CaratEnum::collection(),
                'scraps' => [],
            ];
            foreach (SourceEnum::cases() as $sourceEnum) {
                $scrap = $this->buildScrapSummary($sourceEnum, $ttl);
                if ($scrap) {
                    $result['scraps'] = $scrap;
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
            'variants' => array_fill_keys(SourceEnum::names(), null),
        ];

        $variants = Variant::query()
            ->whereIn('scrap_id', $scrap->id)
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
