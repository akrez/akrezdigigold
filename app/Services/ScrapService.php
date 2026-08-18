<?php

namespace App\Services;

use App\Enums\CaratEnum;
use App\Enums\SourceEnum;
use App\Models\Page;
use App\Models\Product;
use App\Models\Scrap;
use App\Models\Variant;
use Illuminate\Support\Facades\Log;

class ScrapService
{
    const ERROR_CONNECTION = 0;

    const ERROR_JSON = -1;

    const ERROR_SIZE = -2;

    const ERROR_PRICE = -3;

    const ERROR_CARAT = -4;

    const ERROR_EXTERNAL_ID = -3;

    const ERROR_PRODUCT = -544;

    const ERROR_VARIANT = -544;

    const ERROR_CATCH = -555;

    protected function getIncompleteCounts(int $scrapId): int
    {
        return Page::where('scrap_id', $scrapId)->whereNull('http_status')->count() +
            Product::where('scrap_id', $scrapId)->whereNull('http_status')->count();
    }

    protected function getCompleteCounts(int $scrapId): int
    {
        return Page::where('scrap_id', $scrapId)->whereNotNull('http_status')->count() +
            Product::where('scrap_id', $scrapId)->whereNotNull('http_status')->count() +
            Variant::where('scrap_id', $scrapId)->count();
    }

    protected function logError(\Exception|\Throwable $e): void
    {
        Log::error($e->getMessage(), $e->getTrace());
    }

    protected function completeScrap(Scrap $scrap): bool
    {
        return $scrap->update(['completed_at' => now()]);
    }

    protected function completeProduct(Product $product, int $httpStatus): bool
    {
        return $product->update(['http_status' => $httpStatus]);
    }

    protected function completePage(Page $page, int $httpStatus): bool
    {
        return $page->update(['http_status' => $httpStatus]);
    }

    protected function startScrap(Scrap $scrap): bool
    {
        return $scrap->update(['started_at' => now()]);
    }

    protected function isScrapStarted(Scrap $scrap): bool
    {
        return (bool) $scrap->started_at;
    }

    protected function isScrapCompleted(Scrap $scrap): bool
    {
        return (bool) $scrap->completed_at;
    }

    public function firstOrCreateScrap(SourceEnum $sourceEnum, string $scrapKey): ?Scrap
    {
        try {
            return Scrap::firstOrCreate([
                'source' => $sourceEnum->name,
                'scrap_key' => $scrapKey,
            ]);
        } catch (\Exception $e) {
            $this->logError($e);
        }

        return null;
    }

    protected function savePage(int $scrapId, int $number): ?Page
    {
        try {
            return Page::updateOrCreate([
                'scrap_id' => $scrapId,
                'number' => $number,
            ]);
        } catch (\Exception $e) {
            $this->logError($e);
        }

        return null;
    }

    protected function saveProduct(int $scrapId, int $pageId, string $externalId, array $productData): ?Product
    {
        try {
            return Product::updateOrCreate([
                'scrap_id' => $scrapId,
                'page_id' => $pageId,
                'external_id' => $externalId,
            ], [
                'title' => $productData['title'],
                'image_url' => $productData['image_url'] ?? null,
                'product_url' => $productData['product_url'] ?? null,
            ]);
        } catch (\Exception $e) {
            $this->logError($e);
        }

        return null;
    }

    protected function saveVariant(int $scrapId, int $productId, CaratEnum $caratEnum, string $seller, float $size, float $price): ?Variant
    {
        try {
            return Variant::create([
                'scrap_id' => $scrapId,
                'product_id' => $productId,
                'carat' => $caratEnum->name,
                'seller' => $seller,
                'size' => $size,
                'price' => $price,
                'price_per_gram' => $price / $size,
            ]);
        } catch (\Exception $e) {
            $this->logError($e);
        }

        return null;
    }

    protected function sanitizeNumber(string $string): string
    {
        return preg_replace('/[^\\d.]+/', '', $string);
    }

    protected function formatItem(string $title, float $size, string $url, ?string $image, string $seller, float $price): array
    {
        return [
            'title' => $title,
            'size' => $size,
            'url' => $url,
            'image' => $image,
            'seller' => $seller,
            'source' => $this->source->value,
            'price' => ['r' => $price, 'f' => number_format($price)],
            'pricePerGram' => ['r' => $price / $size, 'f' => number_format($price / $size)],
        ];
    }
}
