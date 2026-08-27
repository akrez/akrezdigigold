<?php

namespace App\Services\Prices;

use App\Enums\SourceEnum;

class TechnogoldPriceService extends PriceService
{
    protected function getEndpoint(): string
    {
        return 'https://api2.technogold.gold/customer/tradeables/price-history?type=daily';
    }

    public function getSourceEnum(): SourceEnum
    {
        return SourceEnum::TECHNOGOLD;
    }

    protected function extractPrice(mixed $payload): ?int
    {
        $data = $payload['results']['data'] ?? null;
        if (! is_array($data) || empty($data)) {
            return null;
        }
        $last = end($data);
        $price = $last['sell_price'] ?? $last['buy_price'] ?? null;
        if ($price === null) {
            return null;
        }

        return intval(round(floatval($price)));
    }
}
