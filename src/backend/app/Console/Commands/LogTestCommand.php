<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class LogTestCommand extends Command {
    protected $signature = 'log:test';
    protected $description = 'Generate test log messages with different levels';

    public function handle() {
        $this->info('Generating log messages...');

        Log::debug('This is a DEBUG message');
        Log::info('This is an INFO message');
        Log::notice('This is a NOTICE message');
        Log::warning('This is a WARNING message');
        Log::error('This is an ERROR message');
        Log::critical('This is a CRITICAL message');
        Log::alert('This is an ALERT message');
        Log::emergency('This is an EMERGENCY message');

        $this->info('Log messages sent! Check your logs.');
    }
}
