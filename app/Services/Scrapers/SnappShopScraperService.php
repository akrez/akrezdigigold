<?php

namespace App\Services\Scrapers;

use App\Enums\CaratEnum;
use App\Models\Page;
use App\Models\Product;
use App\Models\Scrap;
use App\Services\ScrapService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class SnappShopScraperService extends ScrapService
{
    protected const API_BASE = 'https://apix.snappshop.ir';

    public function analyze(Scrap $scrap): void
    {
        if ($this->isScrapCompleted($scrap)) {
            return;
        }

        if (! $this->isScrapStarted($scrap)) {
            $this->createPages($scrap);
        }
        $this->createProducts($scrap);
        $this->createVariants($scrap);

        if (
            $this->getCompleteCounts($scrap->id) &&
            ! $this->getIncompleteCounts($scrap->id)
        ) {
            $this->completeScrap($scrap);
        }
    }

    protected function callSearch(array $pages): array
    {
        return Http::pool(fn ($pool) => collect($pages)->map(fn ($page) => $pool->as($page)
            ->withHeaders($this->getHeaders())
            ->timeout(30)
            ->post(self::API_BASE.'/landing/v2?lat=35.00&lng=51.00', [
                'exclude_filters' => true,
                'is_available' => true,
                'page_type' => 'category',
                'render' => 4,
                'skip' => $page,
                'slug' => 'gold-bullion',
            ])
        )->toArray());
    }

    public function callProduct(array $productIds): array
    {
        return Http::pool(fn ($pool) => collect($productIds)->map(fn ($productId) => $pool->as($productId)
            ->withHeaders($this->getHeaders())
            ->timeout(30)
            ->get(self::API_BASE.'/products/v2/'.$productId, [
                'lat' => 35.00,
                'lng' => 51.00,
            ])
        )->toArray());
    }

    public function createPages(Scrap $scrap): void
    {
        $totalPages = $this->processPages($scrap, 1, 1);
        if ($totalPages <= 1) {
            return;
        }

        $totalPages = $this->processPages($scrap, 2, $totalPages);

        $this->startScrap($scrap);
    }

    protected function processPages(Scrap $scrap, int $start, int $end)
    {
        $totalPages = 0;
        try {
            $remainingPages = range($start, $end);
            foreach (array_chunk($remainingPages, 50) as $chunk) {
                $responses = $this->callSearch($chunk);
                foreach ($responses as $pageNumber => $response) {
                    if ($response instanceof ConnectionException) {
                        $this->logError($response);
                    } elseif ($response->successful()) {
                        $totalPages = intval($totalPages ?: $response->json('data.structure.2.pagination.total_pages'));
                        $page = $this->savePage($scrap->id, $pageNumber);
                        if ($page) {
                            $this->completePage(
                                $page,
                                $this->createProductByResponse($scrap, $page, $response)
                            );
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logError($e);
        }

        return $totalPages;
    }

    public function createProducts(Scrap $scrap): void
    {
        try {
            $allPages = Page::where('scrap_id', $scrap->id)
                ->where(function ($q) {
                    return $q->whereNull('http_status')->orWhereIn('http_status', [429]);
                })
                ->get()
                ->pluck(null, 'number');

            if ($allPages->isEmpty()) {
                return;
            }

            foreach (array_chunk($allPages->pluck('number')->toArray(), 10) as $pageNumbers) {
                $responses = $this->callSearch($pageNumbers);
                foreach ($responses as $pageNumber => $response) {
                    if (isset($allPages[$pageNumber])) {
                        $page = $allPages[$pageNumber];
                        $this->completePage(
                            $page,
                            $this->createProductByResponse($scrap, $page, $response)
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logError($e);
        }
    }

    protected function createProductByResponse(Scrap $scrap, Page $page, mixed $response): int
    {
        try {
            if ($response instanceof ConnectionException) {
                $this->logError($response);

                return static::ERROR_CONNECTION;
            }
            if (! $response->successful()) {
                return $response->status();
            }
            $items = (array) $response->json('data.structure.2.items', []);
            $saved = false;
            foreach ($items as $item) {
                $productId = str_replace('snp-', '', basename($item['href'] ?? ''));
                if ($productId) {
                    $product = $this->saveProduct(
                        $scrap->id,
                        $page->id,
                        $productId,
                        [
                            'title' => $item['title'] ?? '',
                            'image_url' => $item['image']['src'] ?? null,
                            'product_url' => 'https://snappshop.ir/product/snp-'.$productId,
                        ]
                    );
                    if ($product) {
                        $saved = true;
                    }
                }
            }

            return $saved ? $response->status() : static::ERROR_JSON;
        } catch (\Exception $e) {
            $this->logError($e);
        }

        return static::ERROR_CATCH;
    }

    public function createVariants(Scrap $scrap): void
    {
        Product::where('scrap_id', $scrap->id)
            ->where(function ($q) {
                return $q->whereNull('http_status')->orWhereIn('http_status', [429]);
            })
            ->chunkById(60, function ($products) use ($scrap) {
                try {
                    $products = $products->keyBy('external_id');
                    $externalIds = $products->map(fn ($p) => $p->external_id)->toArray();
                    $responses = $this->callProduct($externalIds);
                    foreach ($responses as $productId => $response) {
                        $product = $products[$productId] ?? null;
                        if ($product) {
                            $this->completeProduct(
                                $product,
                                $this->processProduct($scrap->id, $product, $response)
                            );
                        }
                    }
                } catch (\Exception $e) {
                    $this->logError($e);
                }
            });
    }

    protected function processProduct(int $scrapId, Product $product, $response): int
    {
        try {
            if ($response instanceof ConnectionException) {
                $this->logError($response);

                return static::ERROR_CONNECTION;
            }
            if (! $response->successful()) {
                return $response->status();
            }
            $data = (array) $response->json('data', []);
            $sellers = [];
            foreach ($data['vendors'] ?? [] as $vendor) {
                $sellers[$vendor['id']] = $vendor['title'] ?? '';
            }
            $attributes = [];
            foreach ($data['configurable_attribute'] ?? [] as $configurableAttribute) {
                $attributes[$configurableAttribute['value']['id']] = $configurableAttribute['value']['title'];
            }
            $saved = false;
            foreach ($data['variants'] ?? [] as $variant) {
                foreach ($variant['vendor'] ?? [] as $vendor) {
                    $variant = $this->saveVariant(
                        $scrapId,
                        $product->id,
                        $vendor['vendor_product_info_id'] ?? '',
                        $this->extractCarat($data['attributes'] ?? []),
                        trim($sellers[$vendor['vendor_id']] ?? ''),
                        $this->extractSize($attributes, $variant['attribute_ids'] ?? []),
                        floatval(empty($vendor['special_price']) ? ($vendor['price'] ?? 0) : $vendor['special_price'])
                    );
                    if ($variant) {
                        $saved = true;
                    }
                }
            }

            return $saved ? $response->status() : static::ERROR_JSON;
        } catch (\Exception $e) {
            $this->logError($e);
        }

        return static::ERROR_CATCH;
    }

    protected function extractCarat(array $attributes): ?CaratEnum
    {
        foreach ($attributes as $attribute) {
            if (strpos($attribute['title'] ?? '', 'عیار') !== false) {
                $carat = $this->sanitizeNumber($attribute['value'] ?? '');
                if ($carat) {
                    $carat = floatval($carat);
                    switch ($carat) {
                        case 18:
                        case 750:
                            return CaratEnum::CARAT_18;
                        case 24:
                        case 995:
                            return CaratEnum::CARAT_24;
                        case 999:
                        case 999.9:
                            return CaratEnum::CARAT_9999;
                        default:
                    }
                }
            }
        }

        return null;
    }

    protected function extractSize($attributes, $attributeIds): float
    {
        foreach ($attributeIds as $attributeId) {
            if (isset($attributes[$attributeId['attribute_value_id']])) {
                $vazn = $attributes[$attributeId['attribute_value_id']];
                if (strpos($vazn, 'گرم') !== false) {
                    return floatval($this->sanitizeNumber($vazn));
                }
                if (strpos($vazn, 'سوت') !== false) {
                    return floatval($this->sanitizeNumber($vazn)) * 0.001;
                }
            }
        }

        return 0;
    }

    protected function getHeaders(): array
    {
        $v = rand(100, 200);

        return [
            'accept' => '*/*',
            'accept-language' => 'en-US,en;q=0.9,zh-CN;q=0.8,zh;q=0.7,ar;q=0.6,fa;q=0.5',
            'content-type' => 'application/json',
            'origin' => 'https://snappshop.ir',
            'priority' => 'u=1, i',
            'referer' => 'https://snappshop.ir/',
            's-device' => 'DESKTOP',
            's-device-source' => 'shop',
            'sec-ch-ua-platform' => '"Linux"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-site',
            'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/'.$v.'.00 (KHTML, like Gecko) Chrome/145.0.0.0 Safari/'.$v.'.00',
            'x-origin' => 'https://snappshop.ir',
        ];
    }
}
