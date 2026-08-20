<?php

namespace App\Services\Scrapers;

use App\Enums\CaratEnum;
use App\Enums\SourceEnum;
use App\Models\Page;
use App\Models\Product;
use App\Models\Scrap;
use App\Models\Variant;
use Illuminate\Support\Facades\Log;

abstract class ScraperService
{
    const ERROR_CONNECTION = 0;

    const ERROR_JSON = 1;

    const ERROR_CARAT = 4;

    const ERROR_CATCH = 5000;

    public function analyze(string $scrapKey, SourceEnum $sourceEnum): int
    {
        $scrap = $this->firstOrCreateScrap($scrapKey, $sourceEnum);
        if (! $scrap) {
            return 404;
        }
        if ($scrap->completed_at) {
            return 200;
        }

        if (! $scrap->started_at) {
            $this->createPages($scrap);
        }
        $this->createProducts($scrap);
        $this->createVariants($scrap);

        if (
            $scrap->started_at &&
            (Page::notPending($scrap->id)->count() > 0) &&
            (Product::notPending($scrap->id)->count() > 0) &&
            (Page::pending($scrap->id)->count() == 0) &&
            (Product::pending($scrap->id)->count() == 0)
        ) {
            $scrap->update(['completed_at' => now()]);

            return 201;
        }

        return 202;
    }

    abstract public function createPages(Scrap $scrap): void;

    abstract public function createProducts(Scrap $scrap): void;

    abstract public function createVariants(Scrap $scrap): void;

    protected function completePage(Page $page, int $httpStatus): bool
    {
        return $page->update(['http_status' => $httpStatus]);
    }

    protected function completeProduct(Product $product, int $httpStatus): bool
    {
        return $product->update(['http_status' => $httpStatus]);
    }

    protected function startScrap(Scrap $scrap): bool
    {
        return $scrap->update(['started_at' => now()]);
    }

    public function firstOrCreateScrap(string $scrapKey, SourceEnum $sourceEnum): ?Scrap
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

    protected function updateOrCreatePage(int $scrapId, int $number): ?Page
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

    protected function updateOrCreateProduct(int $scrapId, int $pageId, string $externalId, array $productData): ?Product
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

    protected function updateOrCreateVariant(int $scrapId, int $productId, string $externalId, ?CaratEnum $caratEnum, string $seller, float $size, float $price): ?Variant
    {
        try {
            return Variant::updateOrCreate([
                'scrap_id' => $scrapId,
                'product_id' => $productId,
                'external_id' => $externalId ?: null,
            ], [
                'carat' => ($caratEnum ? $caratEnum->name : null),
                'seller' => $seller ?: null,
                'size' => $size ?: null,
                'price' => $price ?: null,
                'price_per_gram' => (($caratEnum && $seller && $price && $size) ? ($price / $size) : null),
            ]);
        } catch (\Exception $e) {
            $this->logError($e);
        }

        return null;
    }

    protected function logError(\Exception|\Throwable $e): void
    {
        Log::error($e->getMessage(), $e->getTrace());
    }

    protected function sanitizeNumber(string $string): string
    {
        return preg_replace('/[^\\d.]+/', '', $string);
    }
}
