<?php

namespace App\Services\Prices;

use App\Enums\SourceEnum;

class MelligoldPriceService extends PriceService
{
    protected function getEndpoint(): string
    {
        return 'https://melligold.com/api/v1/exchange/buy-sell-price/?symbol=XAU18&format=json';
    }

    public function getSourceEnum(): SourceEnum
    {
        return SourceEnum::MELLIGOLD;
    }

    protected function extractPrice(mixed $payload): ?int
    {
        $price = $payload['data']['price_buy'] ?? $payload['data']['price_sell'] ?? null;
        if ($price === null) {
            return null;
        }

        return $this->sanitizeNumber($price);
    }
}
