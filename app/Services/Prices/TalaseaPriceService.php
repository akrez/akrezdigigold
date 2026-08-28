<?php

namespace App\Services\Prices;

use App\Enums\SourceEnum;

class TalaseaPriceService extends PriceService
{
    protected function getEndpoint(): string
    {
        return 'https://api.talasea.ir/api/market/getGoldPrice';
    }

    public function getSourceEnum(): SourceEnum
    {
        return SourceEnum::TALASEA;
    }

    protected function extractPrice(mixed $payload): ?int
    {
        $price = $payload['price'] ?? null;
        if ($price === null) {
            return null;
        }
        $intPrice = $this->sanitizeNumber($price);
        if ($intPrice === null) {
            return null;
        }

        return $intPrice * 1000;
    }
}
