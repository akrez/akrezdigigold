<?php

namespace App\Services\Prices;

use App\Enums\SourceEnum;

class DigikalaPriceService extends PriceService
{
    protected function getEndpoint(): string
    {
        return 'https://api.digikala.com/non-inventory/v1/prices/chart/daily/?asset_type=gold18';
    }

    public function getSourceEnum(): SourceEnum
    {
        return SourceEnum::DIGIKALA;
    }

    protected function extractPrice(mixed $payload): ?int
    {
        $buckets = $payload['buckets'] ?? null;
        if (! is_array($buckets) || empty($buckets)) {
            return null;
        }
        $last = end($buckets);
        $price = $last['price'] ?? null;

        $intPrice = $this->sanitizeNumber($price);
        if ($intPrice === null) {
            return null;
        }

        return $intPrice * 100;
    }
}
