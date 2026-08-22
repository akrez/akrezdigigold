<?php

namespace App\Services;

use App\Enums\CaratEnum;
use App\Enums\SourceEnum;
use App\Models\Scrap;
use App\Models\Variant;
use Illuminate\Support\Facades\Cache;

class ScrapService
{
    protected function getCacheKey(): string
    {
        return __CLASS__.'::'.__FUNCTION__;
    }

    public function clearSummaryCache(): bool
    {
        return Cache::forget($this->getCacheKey());
    }

    public function buildSummaryCache(int $ttl = 3600): array
    {
        return Cache::remember($this->getCacheKey(), $ttl, function () use ($ttl) {
            $result = [
                'date' => now()->format('Y-m-d H:i:s'),
                'scraps' => [],
                'items' => array_fill_keys(CaratEnum::names(), []),
                'total_count' => 0,
            ];

            foreach (SourceEnum::names() as $sourceName) {
                $scrap = Scrap::query()
                    ->where('source', $sourceName)
                    ->whereTime('created_at', '>=', now()->subSeconds($ttl))
                    ->whereNotNull('completed_at')
                    ->first();
                if ($scrap) {
                    $result['scraps'][$scrap->id] = [
                        'source' => $scrap->source,
                    ];
                }
            }

            if (! $result['scraps']) {
                return $result;
            }

            $scrapIds = array_keys($result['scraps']);
            $result['scraps'] = array_values($result['scraps']);

            $variants = Variant::query()
                ->whereIn('scrap_id', $scrapIds)
                ->with(['product'])
                ->whereNotNull('carat')
                ->where('size', '>', 0)
                ->where('price_per_gram', '>', 0)
                ->orderBy('price_per_gram')
                ->get();

            $result['total_count'] = $variants->count();

            $variants->each(function ($variant) use (&$result) {
                $price = intval($variant->price);
                $pricePerGram = intval($price / $variant->size);
                $result['items'][$variant->carat][] = [
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
