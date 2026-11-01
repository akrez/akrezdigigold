<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class AiController extends Controller
{
    public function index(Request $request): View
    {
        return view('ai.index', []);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'api_base_url' => ['required', 'url'],
            'api_key' => ['required', 'string'],
            'model' => ['required', 'string'],
            'prompt' => ['required', 'string'],
            'secret' => ['required', 'string'],
        ]);

        $shellSecret = (string) config('app.shell_secret');
        $secret = (string) $data['secret'];

        abort_unless($shellSecret !== '' && hash_equals($shellSecret, $secret), 403);

        try {
            $agentPath = base_path('agent');
            $workingDir = base_path();

            $process = new Process([
                $agentPath,
                '--provider', 'openai',
                '--api-base-url', $data['api_base_url'],
                '--api-key', $data['api_key'],
                '--model', $data['model'],
                '--permission-mode', 'allow',
                '--cwd', $workingDir,
                '-p', $data['prompt'],
            ], $workingDir);

            $process->setTimeout(null);
            $process->run();

            $combined = trim($process->getOutput().$process->getErrorOutput());

            $output = $combined === ''
                ? []
                : preg_split('/\r\n|\r|\n/', $combined);

            return response()->json([
                'isSuccess' => $process->isSuccessful(),
                'resultCode' => $process->getExitCode(),
                'output' => $output,
            ]);

        } catch (ProcessTimedOutException $e) {
            return response()->json([
                'isSuccess' => false,
                'resultCode' => -1,
                'output' => ['ERROR'],
            ], 408);
        }
    }
}
