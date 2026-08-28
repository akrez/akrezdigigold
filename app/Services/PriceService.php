<?php

namespace App\Services;

use App\Enums\CaratEnum;
use App\Enums\SourceEnum;
use App\Models\Price;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PriceService extends Service
{
    const RETRY_TIMES = 3;

    const RETRY_SLEEP_MS = 500;

    const TIMEOUT_SECONDS = 10;

    const CACHE_TTL = 59;

    const CACHE_SUB_SECONDS = 10800;

    const CACHE_KEY_SECTION_CHART = 'chart';

    public function fetch(array $serviceClassNames, ?string $priceKey): ?Price
    {
        try {
            $responses = Http::pool(function (Pool $pool) use ($serviceClassNames) {
                foreach ($serviceClassNames as $serviceClassName) {
                    $service = app($serviceClassName);
                    $pool
                        ->as($serviceClassName)
                        ->withHeaders($service->getHeaders())
                        ->timeout(static::TIMEOUT_SECONDS)
                        ->retry(
                            times: static::RETRY_TIMES,
                            sleepMilliseconds: static::RETRY_SLEEP_MS,
                            when: fn (\Throwable $e) => $e instanceof ConnectionException,
                            throw: false,
                        )
                        ->get($service->getEndpoint());
                }
            });

            foreach ($responses as $serviceClassName => $response) {
                if ($response instanceof Response && $response->successful()) {
                    $service = app($serviceClassName);
                    $price = $service->extractPrice($response->json());
                    if ($price) {
                        $this->store(
                            $service->getSourceEnum(),
                            $service->getCaratEnum(),
                            $price,
                            $priceKey
                        );
                    }
                }
            }

            return null;
        } catch (\Throwable $e) {
            $this->logError($e);

            return null;
        }
    }

    protected function getCacheKey(?string $section): string
    {
        return implode('::', [__CLASS__, __FUNCTION__, $section]);
    }

    public function buildChartCache(): array
    {
        return Cache::remember($this->getCacheKey(self::CACHE_KEY_SECTION_CHART), self::CACHE_TTL, function () {
            $result = [
                'prices' => array_fill_keys(CaratEnum::names(), array_fill_keys(SourceEnum::names(), [])),
            ];
            $prices = Price::query()
                ->whereTime('created_at', '>=', now()->subSeconds(self::CACHE_SUB_SECONDS))
                ->orderBy('created_at', 'ASC')
                ->get();
            foreach ($prices as $price) {
                $result['prices'][$price->carat][$price->source][] = [
                    'price'=> $price->price,
                    'created_at'=> $price->created_at->format('Y-m-d H:i:s'),
                ];
            }
            return $result;
        });
    }

    protected function store(SourceEnum $source, CaratEnum $carat, int $price, ?string $priceKey): ?Price
    {
        try {
            return Price::create([
                'source' => $source->name,
                'carat' => $carat->name,
                'price' => $price,
                'price_key' => $priceKey,
            ]);
        } catch (\Throwable $e) {
            $this->logError($e);

            return null;
        }
    }
}
