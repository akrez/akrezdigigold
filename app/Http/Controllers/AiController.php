<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\Process\Process;

class AiController extends Controller
{
    public function index(Request $request): View
    {
        return view('ai.index', []);
    }

    public function store(Request $request): JsonResponse
    {
        try {

            $data = $request->validate([
                'api_base_url' => ['required', 'url'],
                'api_key' => ['required', 'string'],
                'model' => ['required', 'string'],
                'prompt' => ['required', 'string'],
                'secret' => ['required', 'string'],
            ]);

            $shellSecret = (string) config('app.shell_secret');
            $secret = (string) $data['secret'];

            throw_unless($shellSecret !== '' && hash_equals($shellSecret, $secret), new Exception('Forbidden: Invalid secret', 403));

            $workingDir = base_path();

            $process = new Process([
                $workingDir.'/agent',
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

            $output = $combined === '' ? [] : preg_split('/\r\n|\r|\n/', $combined);

            return $this->makeStoreResponse($process->isSuccessful(), $output);
        } catch (Exception $e) {
            return $this->makeStoreResponse(false, [$e->getMessage()]);
        }
    }

    protected function makeStoreResponse(bool $isSuccess = false, array $output = [])
    {
        return response()->json([
            'isSuccess' => $isSuccess,
            'output' => $output,
        ], $isSuccess ? 200 : 500);
    }
}
