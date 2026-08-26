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
        return __CLASS__.'::'.__FUNCTION__;
    }

    public function clearSummaryCache(): bool
    {
        return Cache::forget($this->getSummaryCacheKey());
    }

    public function buildSummaryCache(int $ttl = 3600): array
    {
        return Cache::remember($this->getSummaryCacheKey(), $ttl, function () use ($ttl) {
            $result = [
                'date' => now()->format('Y-m-d H:i:s'),
                'carats' => CaratEnum::collection(),
                'scraps' => array_fill_keys(SourceEnum::names(), null),
                'items' => array_fill_keys(
                    CaratEnum::names(),
                    array_fill_keys(SourceEnum::names(), [])
                ),
            ];

            $scrapIds = [];
            foreach (SourceEnum::cases() as $sourceEnum) {
                $scrap = Scrap::query()
                    ->where('source', $sourceEnum->name)
                    ->whereTime('created_at', '>=', now()->subSeconds($ttl))
                    ->whereNotNull('completed_at')
                    ->orderBy('completed_at', 'DESC')
                    ->first();
                if ($scrap) {
                    $scrapIds[] = $scrap->id;
                    $result['scraps'][$sourceEnum->name] = [
                        'source' => $sourceEnum->resource(),
                        'completed_at' => $scrap->completed_at,
                    ];
                }
            }

            if (! $scrapIds) {
                return $result;
            }

            $variants = Variant::query()
                ->whereIn('scrap_id', $scrapIds)
                ->with(['scrap', 'product'])
                ->whereNotNull('carat')
                ->where('size', '>', 0)
                ->where('price_per_gram', '>', 0)
                ->orderBy('price_per_gram')
                ->get();

            $variants->each(function ($variant) use (&$result) {
                $price = intval($variant->price);
                $pricePerGram = intval($price / $variant->size);
                $result['items'][$variant->carat][$variant->scrap->source][] = [
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
        });
    }
}
