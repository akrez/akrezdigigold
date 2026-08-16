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
        do {
            dump($this->getIncompleteCounts($scrap->id));
            if (! $this->isScrapStarted($scrap)) {
                $this->createPages($scrap);
            }
            $this->createProducts($scrap);
            $this->createVariants($scrap);
        } while ($this->getIncompleteCounts($scrap->id) > 0);
        $this->completeScrap($scrap);
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
                    $this->createProductByResponse($scrap, $page, $responses[$pageNumber]);
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
                ->whereNull('completed_at')
                ->get()
                ->pluck(null, 'number');
            foreach (array_chunk($allPages->pluck('number')->toArray(), 10) as $pageNumbers) {
                $responses = $this->callSearch($pageNumbers);
                foreach ($responses as $pageNumber => $response) {
                    $this->createProductByResponse($scrap, $allPages[$pageNumber], $response);
                }
            }
        } catch (\Exception $e) {
            $this->logError($e);
        }
    }

    protected function createProductByResponse(Scrap $scrap, Page $page, mixed $response)
    {
        if ($response instanceof ConnectionException) {
            $this->logError($response);
        } elseif ($response->successful()) {
            try {
                foreach ($response->json('data.products', []) as $product) {
                    $result['ids'][] = $this->saveProduct($scrap->id, $page->id, $product['id'], [
                        'title' => ($product['title_fa'] ?? $product['title'] ?? ''),
                        'image_url' => ($product['images']['main']['url'][0] ?? null),
                        'product_url' => 'https://www.digikala.com/product/'.$product['id'],
                    ])?->id;
                    $this->completePage($page);
                }
            } catch (\Exception $e) {
                $this->logError($e);
            }
        }
    }

    public function createVariants(Scrap $scrap): void
    {
        Product::where('scrap_id', $scrap->id)
            ->whereNull('completed_at')
            ->chunkById(60, function ($products) {
                try {

                    $products = $products->keyBy('id');

                    $responses = Http::pool(fn ($pool) => $products->map(fn ($product) => $pool
                        ->as($product->id)
                        ->withHeaders($this->getHeaders())
                        ->get(self::API_BASE."/v2/product/{$product->external_id}/")
                    )->toArray());

                    foreach ($responses as $productId => $response) {
                        if ($response instanceof ConnectionException) {
                        } elseif ($response->successful()) {
                            foreach ($response->json('data.product.variants', []) as $variant) {
                                $result['ids'] = $this->saveVariant(
                                    $productId,
                                    $this->extractCarat($response->json('data.product')),
                                    ($variant['seller']['title'] ?? ''),
                                    $this->extractSize($variant),
                                    floatval($variant['price']['selling_price'] ?? 0)
                                )?->id;
                                $this->completeProduct($products[$productId]);
                            }
                        }
                    }
                } catch (\Exception $e) {
                    $this->logError($e);
                }
            });
    }

    protected function extractCarat(array $data): CaratEnum
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

        return CaratEnum::CARAT_0;
    }

    protected function extractSize(array $data): ?float
    {
        return isset($data['size']['title'])
            ? floatval($this->sanitizeNumber($data['size']['title']))
            : null;
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
