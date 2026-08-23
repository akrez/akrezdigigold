<?php

namespace App\Http\Controllers;

use App\Services\ScrapService;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function index(Request $request)
    {
        $scrapService = app(ScrapService::class);

        return view('home.index', [
            'summary' => $scrapService->buildSummaryCache(),
        ]);
    }
}
