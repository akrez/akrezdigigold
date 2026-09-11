<?php

namespace App\Http\Controllers;

use App\Services\PriceService;
use App\Services\ScrapService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $scrapService = app(ScrapService::class);
        $priceService = app(PriceService::class);

        return view('home.index', [
            'summary' => $scrapService->buildShortSummaryCache(),
            'chart' => $priceService->buildChartCache(),
        ]);
    }

    public function optimize(Request $request)
    {
        Artisan::call('app:optimize');

        return response()->json([
            'ok' => true,
            'output' => Artisan::output(),
        ]);
    }
}
