<?php

namespace App\Console\Commands\MpmSystemChecks;

use Illuminate\Console\Command;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;

class HorizonCheckCommand extends Command {
    protected $signature = 'mpm-system-checks:horizon';
    protected $description = 'Check Horizon queue worker status';

    public function handle(): int {
        $this->info('Checking Horizon queue worker status...');

        try {
            $masters = app(MasterSupervisorRepository::class)->all();

            if (empty($masters)) {
                $this->error('Horizon is inactive — no master supervisor running.');

                return Command::FAILURE;
            }

            $allPaused = true;
            foreach ($masters as $master) {
                if (($master->status ?? 'inactive') !== 'paused') {
                    $allPaused = false;

                    break;
                }
            }

            if ($allPaused) {
                $this->error('Horizon is paused — all master supervisors are paused.');

                return Command::FAILURE;
            }

            $this->info('Horizon is running with '.count($masters).' master supervisor(s).');

            $supervisors = app(SupervisorRepository::class)->all();
            $totalProcesses = 0;

            foreach ($supervisors as $supervisor) {
                $totalProcesses += count($supervisor->processes ?? []);
            }

            if ($totalProcesses === 0) {
                $this->error('No active worker processes found.');

                return Command::FAILURE;
            }

            $this->info("Active worker processes: {$totalProcesses}.");

            $failedCount = app(JobRepository::class)->countRecentlyFailed();

            if ($failedCount > 0) {
                $this->warn("Recently failed jobs: {$failedCount}.");
            }

            $pendingCount = app(JobRepository::class)->countPending();
            $this->info("Pending jobs: {$pendingCount}.");

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Horizon check failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
