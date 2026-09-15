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
            'summary' => $scrapService->buildShortSummaryCache(),
            'chart' => $priceService->buildChartCache(),
        ]);
    }

    public function shell(Request $request)
    {
        return view('home.shell', []);
    }

    public function run(Request $request)
    {
        $request->validate([
            'command' => ['required', 'string', 'max:2000'],
        ]);

        $command = $request->input('command');

        $output = [];
        $resultCode = 0;

        exec(
            $command.' 2>&1',
            $output,
            $resultCode
        );

        return response()->json([
            'resultCode' => $resultCode,
            'output' => (array) $output,
        ]);
    }
}
