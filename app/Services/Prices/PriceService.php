<?php

namespace App\Services\Prices;

use App\Enums\SourceEnum;
use App\Services\Service;

abstract class PriceService extends Service
{
    abstract public function getSourceEnum(): SourceEnum;

    abstract protected function getEndpoint(): string;

    abstract protected function extractPrice(mixed $payload): ?int;

    protected function getHeaders(): array
    {
        $v = rand(100, 200);

        return [
            'accept' => 'application/json',
            'user-agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/'.$v.'.00 (KHTML, like Gecko) Chrome/'.$v.'.0.0.0 Safari/'.$v.'.00',
        ];
    }
}
