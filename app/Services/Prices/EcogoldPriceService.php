<?php

namespace App\Services\Prices;

use App\Enums\SourceEnum;

class EcogoldPriceService extends PriceService
{
    public function getEndpoint(): string
    {
        return 'https://backend.ecogold.ir/api/prices/otc';
    }

    public function getSourceEnum(): SourceEnum
    {
        return SourceEnum::ECOGOLD;
    }

    public function extractPrice(mixed $payload): ?int
    {
        $items = $payload['data'] ?? null;
        if (! is_array($items)) {
            return null;
        }

        foreach ($items as $item) {
            if (($item['symbol'] ?? null) === 'GOLD18-IRT') {
                $price = $item['buy_price'] ?? null;
                if ($price === null) {
                    return null;
                }

                $intPrice = $this->sanitizeNumber($price);
                if ($intPrice === null) {
                    return null;
                }

                return (int) $intPrice;
            }
        }

        return null;
    }
}
