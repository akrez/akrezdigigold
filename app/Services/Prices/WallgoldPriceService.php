<?php

namespace App\Services\Prices;

use App\Enums\SourceEnum;

class WallgoldPriceService extends PriceService
{
    protected function getEndpoint(): string
    {
        return 'https://wallgold.ir/gold-price/wp-json/wgx/v1/chart/?key=gold18k&period=daily';
    }

    public function getSourceEnum(): SourceEnum
    {
        return SourceEnum::WALLGOLD;
    }

    protected function getHeaders(): array
    {
        return [
            'accept' => 'application/json, text/plain, */*',
            'accept-language' => 'en-US,en;q=0.9,fa;q=0.8',
            'origin' => 'https://wallgold.ir',
            'referer' => 'https://wallgold.ir/gold-price/',
            'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'sec-ch-ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Linux"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-origin',
        ];
    }

    protected function extractPrice(mixed $payload): ?int
    {
        if (isset($payload['data']['price'])) {
            return $this->sanitizeNumber($payload['data']['price']);
        }
        if (isset($payload['data']['buckets']) && is_array($payload['data']['buckets'])) {
            $last = end($payload['data']['buckets']);
            if (is_array($last) && isset($last['price'])) {
                return $this->sanitizeNumber($last['price']);
            }
        }
        if (isset($payload['price'])) {
            return $this->sanitizeNumber($payload['price']);
        }

        return null;
    }
}
