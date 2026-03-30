<?php

namespace App\Console\Commands\MpmSystemChecks;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class StorageCheckCommand extends Command {
    protected $signature = 'mpm-system-checks:storage';
    protected $description = 'Check storage configuration and file operations';

    public function handle(): int {
        $this->info('Checking storage configuration...');

        $defaultDisk = config('filesystems.default');
        $this->info("Default disk: {$defaultDisk}");

        try {
            $testFileName = 'system-check-'.time().'.txt';
            $testContent = 'MicroPowerManager system check test file';

            $this->info('Testing write operation...');
            Storage::disk($defaultDisk)->put($testFileName, $testContent);

            $this->info('Testing read operation...');
            if (!Storage::disk($defaultDisk)->exists($testFileName)) {
                $this->error('File was not created.');

                return Command::FAILURE;
            }

            $content = Storage::disk($defaultDisk)->get($testFileName);
            if ($content !== $testContent) {
                $this->error('File content does not match.');

                return Command::FAILURE;
            }

            $this->info('Testing delete operation...');
            $deleted = Storage::disk($defaultDisk)->delete($testFileName);

            if (!$deleted) {
                $this->error('File was not deleted.');

                return Command::FAILURE;
            }

            $this->info('Storage check passed!');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error('Storage check failed: '.$e->getMessage());

            return Command::FAILURE;
        }
    }
}
