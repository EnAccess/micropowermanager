<?php

namespace App\Console\Commands\MpmSystemChecks;

use Illuminate\Console\Command;

class MpmSystemChecksCommand extends Command {
    protected $signature = 'mpm-system-checks';
    protected $description = 'Run all system checks for MicroPowerManager';

    public function handle(): int {
        $this->info('Running MicroPowerManager system checks...');
        $this->newLine();

        $checks = [
            'mpm-system-checks:logging' => 'Logging',
            'mpm-system-checks:redis-connection' => 'Redis Connection',
            'mpm-system-checks:storage' => 'Storage',
        ];

        $failed = false;

        foreach ($checks as $command => $label) {
            $this->line("Running {$label} check...");

            try {
                $exitCode = $this->call($command);

                if ($exitCode !== Command::SUCCESS) {
                    $failed = true;
                }
            } catch (\Exception $e) {
                $this->error("{$label} check failed: ".$e->getMessage());
                $failed = true;
            }

            $this->newLine();
        }

        if ($failed) {
            $this->error('Some system checks failed.');

            return Command::FAILURE;
        }

        $this->info('All system checks passed!');

        return Command::SUCCESS;
    }
}
