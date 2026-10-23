<?php

namespace App\Http\Controllers;

use App\Services\PriceService;
use App\Services\ScrapService;
use Illuminate\Http\Request;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

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

        $process = Process::fromShellCommandline($command);
        $process->setTimeout(60);

        try {
            $process->run();
        } catch (ProcessTimedOutException $e) {
            return response()->json([
                'resultCode' => -1,
                'output' => ['Process timed out after 60 seconds.'],
            ], 408);
        }

        $combined = trim($process->getOutput().$process->getErrorOutput());

        $output = $combined === '' ? [] : preg_split('/\r\n|\r|\n/', $combined);

        return response()->json([
            'resultCode' => $process->getExitCode(),
            'output' => (array) $output,
        ]);
    }
}
