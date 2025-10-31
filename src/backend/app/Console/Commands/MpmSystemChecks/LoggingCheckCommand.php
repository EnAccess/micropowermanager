<?php

namespace App\Console\Commands\MpmSystemChecks;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class LoggingCheckCommand extends Command {
    protected $signature = 'mpm-system-checks:logging';
    protected $description = 'Check logging system configuration';

    public function handle(): int {
        $this->info('Checking logging configuration...');

        try {
            Log::debug('System check: DEBUG level test');
            Log::info('System check: INFO level test');
            Log::notice('System check: NOTICE level test');
            Log::warning('System check: WARNING level test');
            Log::error('System check: ERROR level test');
            Log::critical('System check: CRITICAL level test');

            $this->info('Log messages sent! Check your logs.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Logging check failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
