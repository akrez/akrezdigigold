<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AppOptimizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:optimize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'App Optimize Command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        foreach ([
            'optimize:clear',
            'optimize',
            'package:discover',
        ] as $command) {
            $this->call($command) == 0
                ? $this->components->success($command)
                : $this->components->warn($command);
        }
    }
}
