<?php

namespace App\Console\Commands;

use App\Enums\SourceEnum;
use App\Services\Prices\DigikalaPriceService;
use App\Services\Prices\MelligoldPriceService;
use App\Services\Prices\MilligoldPriceService;
use App\Services\Prices\TalaseaPriceService;
use App\Services\Prices\TechnogoldPriceService;
use App\Services\Prices\WallgoldPriceService;
use App\Services\PriceService;
use Illuminate\Console\Command;

class PriceFetchCommand extends Command
{
    protected $signature = 'price:fetch {--source= : Source to fetch}  {--key= : fetch key (default: YmdH0000)}';

    protected $description = 'price fetch';

    public function handle(): int
    {
        $source = $this->option('source');

        $priceKey = $this->option('key') ?? now()->format('YmdH0000');

        $services = $this->detectServices($source);
        if (! $services) {
            $this->error('Invalid Source');

            return Command::FAILURE;
        }

        app(PriceService::class)->fetch($services, $priceKey);

        return self::SUCCESS;
    }

    protected function detectServices(?string $serviceName): array
    {
        $serviceName = strtoupper($serviceName);

        $services = [
            SourceEnum::DIGIKALA->name => DigikalaPriceService::class,
            SourceEnum::WALLGOLD->name => WallgoldPriceService::class,
            SourceEnum::TECHNOGOLD->name => TechnogoldPriceService::class,
            SourceEnum::TALASEA->name => TalaseaPriceService::class,
            SourceEnum::MELLIGOLD->name => MelligoldPriceService::class,
            SourceEnum::MILLIGOLD->name => MilligoldPriceService::class,
        ];
        if (! $serviceName) {
            return array_values($services);
        }

        if (array_key_exists($serviceName, $services)) {
            return [$services[$serviceName]];
        }

        return [];
    }
}
