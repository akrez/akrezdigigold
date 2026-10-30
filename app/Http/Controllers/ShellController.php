<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

class ShellController extends Controller
{
    public function create(Request $request): View
    {
        return view('shell.create', []);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'secret' => ['required', 'string'],
            'command' => ['required', 'string', 'max:2000'],
        ]);

        $shellSecret = (string) config('app.shell_secret');
        $secret = (string) $request->input('secret');
        $command = (string) $request->input('command');

        abort_unless($shellSecret && hash_equals($shellSecret, $secret), 403);

        try {
            $process = Process::fromShellCommandline($command);
            $process->setTimeout(300);
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
