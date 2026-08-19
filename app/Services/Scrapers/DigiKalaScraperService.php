<?php

namespace App\Services\Scrapers;

use App\Enums\CaratEnum;
use App\Models\Page;
use App\Models\Product;
use App\Models\Scrap;
use App\Services\ScrapService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class DigiKalaScraperService extends ScrapService
{
    protected const API_BASE = 'https://api.digikala.com';

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
            ->get(self::API_BASE.'/v1/categories/bullion/search/', [
                'has_selling_stock' => 1,
                'page' => $page,
                'sort' => 7,
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
                    $totalPages = $response->json('data.pager.total_pages');
                }
            }
        } catch (\Exception $e) {
            $this->logError($e);
        }

        if (empty($totalPages)) {
            return;
        }

        try {
            foreach (range(1, $totalPages) as $pageNumber) {
                $page = $this->savePage($scrap->id, $pageNumber);
                if (isset($responses[$pageNumber])) {
                    $this->completePage(
                        $page,
                        $this->createProductByResponse($scrap, $page, $responses[$pageNumber])
                    );
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
                ->where(function ($q) {
                    return $q->whereNull('http_status')->orWhereIn('http_status', [429]);
                })
                ->get()
                ->pluck(null, 'number');
            foreach (array_chunk($allPages->pluck('number')->toArray(), 10) as $pageNumbers) {
                $responses = $this->callSearch($pageNumbers);
                foreach ($responses as $pageNumber => $response) {
                    $page = $allPages[$pageNumber];
                    $this->completePage(
                        $page,
                        $this->createProductByResponse($scrap, $page, $response)
                    );
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
            $items = (array) $response->json('data.products', []);
            $saved = false;
            foreach ($items as $product) {
                $product = $this->saveProduct($scrap->id, $page->id, $product['id'], [
                    'title' => ($product['title_fa'] ?? $product['title'] ?? ''),
                    'image_url' => ($product['images']['main']['url'][0] ?? null),
                    'product_url' => 'https://www.digikala.com/product/'.$product['id'],
                ]);
                if ($product) {
                    $saved = true;
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
                    $products = $products->keyBy('id');
                    $responses = Http::pool(fn ($pool) => $products->map(fn ($product) => $pool
                        ->as($product->id)
                        ->withHeaders($this->getHeaders())
                        ->get(self::API_BASE."/v2/product/{$product->external_id}/")
                    )->toArray());
                    foreach ($responses as $productId => $response) {
                        $product = $products[$productId];
                        $this->completeProduct(
                            $product,
                            $this->processProduct($scrap->id, $product, $response)
                        );

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
            $data = (array) $response->json('data.product.variants');
            $saved = false;
            foreach ($data as $variant) {
                $variant = $this->saveVariant(
                    $scrapId,
                    $product->id,
                    $variant['id'] ?? '',
                    $this->extractCarat($response->json('data.product')),
                    trim($variant['seller']['title'] ?? null),
                    $this->extractSize($variant),
                    floatval($variant['price']['selling_price'] ?? 0)
                );
                if ($variant) {
                    $saved = true;
                }
            }

            return $saved ? $response->status() : static::ERROR_JSON;
        } catch (\Exception $e) {
            $this->logError($e);
        }

        return static::ERROR_CATCH;
    }

    protected function extractCarat(array $data): ?CaratEnum
    {
        foreach ($data['specifications'] as $specification) {
            foreach ($specification['attributes'] as $attribute) {
                if (strpos($attribute['title'], 'عیار') !== false) {
                    foreach ($attribute['values'] as $attributeValue) {
                        $carat = $this->sanitizeNumber($attributeValue);
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
            }
        }

        return null;
    }

    protected function extractSize(array $data): float
    {
        return isset($data['size']['title'])
            ? floatval($this->sanitizeNumber($data['size']['title']))
            : 0;
    }

    protected function getHeaders()
    {
        return [
            'accept' => 'application/json, text/plain, */*',
            'accept-language' => 'en-US,en;q=0.9,fa;q=0.8',
            'x-web-client' => 'desktop',
            'x-web-client-id' => 'web',
            'x-web-optimize-response' => '1',
        ];
    }
}
