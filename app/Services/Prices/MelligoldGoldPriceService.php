<?php

namespace App\Services\Prices;

use App\Enums\SourceEnum;
use App\Models\Price;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class MelligoldPriceService extends PriceService
{
    protected const COOKIE_BASE = 'https://melligold.com';

    protected function getEndpoint(): string
    {
        return self::COOKIE_BASE.'/api/v1/exchange/buy-sell-price/?symbol=XAU18&format=json';
    }

    public function getSourceEnum(): SourceEnum
    {
        return SourceEnum::MELLIGOLD;
    }

    protected function getHeaders(): array
    {
        return [
            'accept' => 'application/json, text/plain, */*',
            'accept-language' => 'en-US,en;q=0.9,fa;q=0.8',
            'origin' => 'https://melligold.com',
            'referer' => 'https://melligold.com/',
            'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'sec-ch-ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
            'sec-ch-ua-mobile' => '?0',
            'sec-ch-ua-platform' => '"Linux"',
            'sec-fetch-dest' => 'empty',
            'sec-fetch-mode' => 'cors',
            'sec-fetch-site' => 'same-origin',
        ];
    }

    public function fetch(): ?Price
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= static::RETRY_TIMES; $attempt++) {
            try {
                $response = $this->fetchWithCookies();
                if ($response->successful()) {
                    $payload = $response->json();
                    $price = $this->extractPrice($payload);
                    if ($price !== null && $price > 0) {
                        return $this->store($price, $payload);
                    }
                }

                $this->logAttempt($attempt, $response, null);
            } catch (\Throwable $e) {
                $lastException = $e;
                $this->logAttempt($attempt, null, $e);
            }

            if ($attempt < static::RETRY_TIMES) {
                usleep(static::RETRY_SLEEP_MS * 1000);
            }
        }

        if ($lastException) {
            $this->logError($lastException);
        }

        return null;
    }

    protected function fetchWithCookies(): Response
    {
        $cookieResponse = Http::timeout(static::TIMEOUT_SECONDS)
            ->retry(0)
            ->withHeaders($this->getHeaders())
            ->get(self::COOKIE_BASE);

        $cookies = [];
        foreach ($cookieResponse->cookies() as $cookie) {
            $cookies[$cookie->getName()] = $cookie->getValue();
        }

        return Http::timeout(static::TIMEOUT_SECONDS)
            ->retry(0)
            ->withHeaders($this->getHeaders())
            ->withCookies($cookies, 'melligold.com')
            ->get($this->getEndpoint());
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
