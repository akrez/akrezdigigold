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
        $responses = $this->callSearch([1]);

        $totalPages = null;
        try {
            foreach ($responses as $response) {
                if ($response instanceof ConnectionException) {
                    $this->logError($response);
                } elseif ($response->successful()) {
                    $totalPages = $response->json('data.structure.2.pagination.total_pages');
                }
            }
        } catch (\Exception $e) {
            $this->logError($e);
        }

        if (empty($totalPages)) {
            return;
        }

        try {
            // ذخیره صفحه اول
            $page = $this->savePage($scrap->id, 1);
            if (isset($responses[1])) {
                $this->createProductByResponse($scrap, $page, $responses[1]);
            }

            // دریافت صفحات دیگر به صورت تکه‌تکه
            $remainingPages = range(2, $totalPages);
            foreach (array_chunk($remainingPages, 50) as $chunk) {
                $responses = $this->callSearch($chunk);
                foreach ($responses as $pageNumber => $response) {
                    $page = $this->savePage($scrap->id, $pageNumber);
                    if ($page) {
                        $this->createProductByResponse($scrap, $page, $response);
                    }
                }
            }

            $this->startScrap($scrap);
        } catch (\Exception $e) {
            $this->logError($e);
        }
    }

    public function createProducts(Scrap $scrap): void
    {
        try {
            $allPages = Page::where('scrap_id', $scrap->id)
                ->whereNull('http_status')
                ->get()
                ->pluck(null, 'number');

            if ($allPages->isEmpty()) {
                return;
            }

            foreach (array_chunk($allPages->pluck('number')->toArray(), 10) as $pageNumbers) {
                $responses = $this->callSearch($pageNumbers);
                foreach ($responses as $pageNumber => $response) {
                    if (isset($allPages[$pageNumber])) {
                        $this->createProductByResponse($scrap, $allPages[$pageNumber], $response);
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logError($e);
        }
    }

    protected function createProductByResponse(Scrap $scrap, Page $page, mixed $response): void
    {
        if ($response instanceof ConnectionException) {
            $this->logError($response);

            return;
        }

        if (! $response->successful()) {
            return;
        }

        try {
            $items = $response->json('data.structure.2.items', []);
            foreach ($items as $item) {
                // استخراج productId از href (مثلاً snp-123456)
                $productId = str_replace('snp-', '', basename($item['href'] ?? ''));

                if (empty($productId)) {
                    continue;
                }

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
                    $this->completePage($page);
                }
            }
        } catch (\Exception $e) {
            $this->logError($e);
        }
    }

    public function createVariants(Scrap $scrap): void
    {
        Product::where('scrap_id', $scrap->id)
            ->whereNull('http_status')
            ->chunkById(60, function ($products) use ($scrap) {
                try {
                    $products = $products->keyBy('external_id');
                    $externalIds = $products->map(fn ($p) => $p->external_id)->toArray();
                    $responses = $this->callProduct($externalIds);
                    foreach ($responses as $productId => $response) {
                        if ($response instanceof ConnectionException) {
                            $this->logError($response);

                            continue;
                        }
                        if (! $response->successful()) {
                            continue;
                        }
                        $data = $response->json('data', []);
                        if (empty($data)) {
                            continue;
                        }
                        $product = $products[$productId] ?? null;
                        if (! $product) {
                            continue;
                        }
                        $carat = $this->extractCarat($data['attributes'] ?? []);
                        $sellers = [];
                        foreach ($data['vendors'] ?? [] as $vendor) {
                            $sellers[$vendor['id']] = $vendor['title'] ?? '';
                        }
                        $attributes = [];
                        foreach ($data['configurable_attribute'] as $configurableAttribute) {
                            $attributes[$configurableAttribute['value']['id']] = $configurableAttribute['value']['title'];
                        }
                        foreach ($data['variants'] ?? [] as $variant) {
                            $size = $this->extractSize($attributes, $variant['attribute_ids'] ?? []);
                            if (empty($size)) {
                                continue;
                            }
                            foreach ($variant['vendor'] ?? [] as $vendor) {
                                $price = floatval(empty($vendor['special_price']) ? ($vendor['price'] ?? 0) : $vendor['special_price']);
                                if ($price <= 0) {
                                    continue;
                                }
                                $seller = $sellers[$vendor['vendor_id']] ?? '';
                                $this->saveVariant(
                                    $scrap->id,
                                    $product->id,
                                    $carat,
                                    $seller,
                                    $size,
                                    $price
                                );
                            }
                        }
                        $this->completeProduct($product);
                    }
                } catch (\Exception $e) {
                    $this->logError($e);
                }
            });
    }

    protected function extractCarat(array $attributes): CaratEnum
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

        return CaratEnum::CARAT_0;
    }

    protected function extractSize($attributes, $attributeIds)
    {
        foreach ($attributeIds as $attributeId) {
            if (isset($attributes[$attributeId['attribute_value_id']])) {
                $vazn = $attributes[$attributeId['attribute_value_id']];
                if (strpos($vazn, 'گرم') !== false) {
                    return floatval($this->sanitizeNumber($vazn));
                }
            }
        }

        return null;
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
