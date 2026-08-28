<?php

namespace App\Http\Controllers;

use App\Services\PriceService;
use App\Services\ScrapService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $scrapService = app(ScrapService::class);
        $priceService = app(PriceService::class);

        return view('home.index', [
            'summary' => $scrapService->buildShortSummaryCache(10),
            'chart' => $priceService->buildChartCache(),
        ]);
    }
}
