<?php

namespace App\Services;

use App\Enums\CaratEnum;
use App\Models\Variant;

class ScrapService
{
    public function summarize(string $scrapKey): array
    {
        $result = array_fill_keys(CaratEnum::names(), []);

        Variant::query()
            ->whereHas('scrap', fn ($q) => $q
                ->where('scrap_key', $scrapKey)
                ->whereNotNull('completed_at')
            )
            ->with(['product'])
            ->whereNotNull('carat')
            ->where('size', '>', 0)
            ->where('price_per_gram', '>', 0)
            ->orderBy('price_per_gram')
            ->get()
            ->each(function ($variant) use (&$result) {
                $price = intval($variant->price);
                $pricePerGram = intval($price / $variant->size);
                $result[$variant->carat][] = [
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
