<?php

namespace App\Services\Scrapers;

use App\Enums\CaratEnum;
use App\Models\Page;
use App\Models\Product;
use App\Models\Scrap;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class DigiKalaScraperService extends ScraperService
{
    protected const API_BASE = 'https://api.digikala.com';

    protected function callPage(array $pages): array
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
        $totalPages = $this->processPages($scrap, 1, 1);
        if ($totalPages > 1) {
            $totalPages = $this->processPages($scrap, 2, $totalPages);
        }

        if (
            $totalPages &&
            (Page::notPending($scrap->id)->count() == $totalPages)
        ) {
            $this->startScrap($scrap);
        }
    }

    protected function processPages(Scrap $scrap, int $start, int $end)
    {
        $totalPages = 0;
        try {
            $remainingPages = range($start, $end);
            foreach (array_chunk($remainingPages, 50) as $chunk) {
                $responses = $this->callPage($chunk);
                foreach ($responses as $pageNumber => $response) {
                    if ($response instanceof ConnectionException) {
                        $this->logError($response);
                    } elseif ($response->successful()) {
                        $totalPages = intval($totalPages ?: $response->json('data.pager.total_pages'));
                        $page = $this->updateOrCreatePage($scrap->id, $pageNumber);
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
            $allPages = Page::pending($scrap->id)
                ->get()
                ->pluck(null, 'number');
            foreach (array_chunk($allPages->pluck('number')->toArray(), 10) as $pageNumbers) {
                $responses = $this->callPage($pageNumbers);
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
                $product = $this->updateOrCreateProduct($scrap->id, $page->id, $product['id'], [
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
        Product::pending($scrap->id)
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
                                $this->processProductVariants($scrap->id, $product, $response)
                            );
                        }
                    }
                } catch (\Exception $e) {
                    $this->logError($e);
                }
            });
    }

    protected function callProduct(array $productIds): array
    {
        return Http::pool(fn ($pool) => collect($productIds)->map(fn ($productId) => $pool->as($productId)
            ->withHeaders($this->getHeaders())
            ->timeout(30)
            ->get(self::API_BASE."/v2/product/{$productId}/")
        )->toArray());
    }

    protected function processProductVariants(int $scrapId, Product $product, $response): int
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
                $variant = $this->updateOrCreateVariant(
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
