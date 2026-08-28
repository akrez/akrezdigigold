<?php

namespace App\Services\Prices;

use App\Enums\SourceEnum;

class WallgoldPriceService extends PriceService
{
    public function getEndpoint(): string
    {
        return 'https://api.wallgold.ir/api/v1/price?side=buy&symbol=GLD_18C_750TMN';
    }

    public function getSourceEnum(): SourceEnum
    {
        return SourceEnum::WALLGOLD;
    }

    public function extractPrice(mixed $payload): ?int
    {
        $price = $payload['result']['price'] ?? null;
        if ($price === null) {
            return null;
        }
        $intPrice = $this->sanitizeNumber($price);
        if ($intPrice === null) {
            return null;
        }

        return $intPrice;
    }
}
