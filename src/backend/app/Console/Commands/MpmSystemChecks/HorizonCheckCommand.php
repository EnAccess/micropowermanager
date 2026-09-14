<?php

namespace App\Console\Commands\MpmSystemChecks;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Horizon\Contracts\JobRepository;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;
use Laravel\Horizon\Contracts\SupervisorRepository;
use Laravel\Horizon\MasterSupervisor;

class HorizonCheckCommand extends Command {
    protected $signature = 'mpm-system-checks:horizon
                            {--local : Restrict the check to the master supervisor of the current host}
                            {--alert : Log a critical message when the check fails}';
    protected $description = 'Check Horizon queue worker status';

    public function handle(): int {
        $this->info('Checking Horizon queue worker status...');

        try {
            $problems = $this->collectProblems();
        } catch (\Exception $e) {
            $problems = ['Horizon check failed: '.$e->getMessage()];
        }

        if (count($problems) === 0) {
            return Command::SUCCESS;
        }

        foreach ($problems as $problem) {
            $this->error($problem);
        }

        if ($this->option('alert')) {
            Log::critical('Horizon is not processing jobs. Recover with: supervisorctl restart horizon', [
                'problems' => $problems,
                'scope' => $this->option('local') ? MasterSupervisor::basename() : 'all hosts',
            ]);
        }

        return Command::FAILURE;
    }

    /**
     * @return string[]
     */
    private function collectProblems(): array {
        $masters = resolve(MasterSupervisorRepository::class)->all();

        if ($this->option('local')) {
            // A master's Redis name is `basename()-<token>`, where the token is generated
            // per PHP process. Scoping by basename is therefore the only way a separate
            // process can match the running master, and is what Horizon's own console
            // commands do.
            $masters = array_filter(
                $masters,
                fn ($master) => Str::startsWith($master->name, MasterSupervisor::basename())
            );
        }

        if (count($masters) === 0) {
            // The master's monitor loop refreshes this record every second under a 15s
            // TTL, so an absent record means the loop has stopped. The OS process can
            // still be alive, and supervisord still reports it as RUNNING.
            return ['Horizon is inactive — no master supervisor heartbeat.'];
        }

        $working = array_filter($masters, fn ($master) => ($master->status ?? 'inactive') !== 'paused');

        if (count($working) === 0) {
            return ['Horizon is paused — all master supervisors are paused.'];
        }

        $this->info('Horizon is running with '.count($working).' master supervisor(s).');

        $problems = [];
        $workerProcesses = 0;

        foreach ($working as $master) {
            $supervisors = resolve(SupervisorRepository::class)->get($master->supervisors ?? []);

            if (count($supervisors) === 0) {
                $problems[] = "Master supervisor {$master->name} has no child supervisor heartbeat.";

                continue;
            }

            foreach ($supervisors as $supervisor) {
                // `processes` maps queue name to the number of worker processes serving it,
                // so the worker total is the sum of its values.
                $workerProcesses += array_sum($supervisor->processes ?? []);
            }
        }

        if ($workerProcesses === 0) {
            $problems[] = 'No active worker processes found.';
        } else {
            $this->info("Active worker processes: {$workerProcesses}.");
        }

        $failedCount = resolve(JobRepository::class)->countRecentlyFailed();

        if ($failedCount > 0) {
            $this->warn("Recently failed jobs: {$failedCount}.");
        }

        $this->info('Pending jobs: '.resolve(JobRepository::class)->countPending().'.');

        return $problems;
    }
}
