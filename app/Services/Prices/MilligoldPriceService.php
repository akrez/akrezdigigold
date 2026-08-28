<?php

namespace App\Services\Prices;

use App\Enums\SourceEnum;

class MilligoldPriceService extends PriceService
{
    public function getEndpoint(): string
    {
        return 'https://milli.gold/api/v1/public/milli-price/detail';
    }

    public function getSourceEnum(): SourceEnum
    {
        return SourceEnum::MILLIGOLD;
    }

    public function extractPrice(mixed $payload): ?int
    {
        $price = $payload['data']['price18'] ?? null;
        if ($price === null) {
            return null;
        }
        $intPrice = $this->sanitizeNumber($price);
        if ($intPrice === null) {
            return null;
        }

        return $intPrice * 100;
    }
}
