<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PackageGenerator extends Command {
    protected $signature = 'micropowermanager:new-package {package-name} {--description= : Optional description for the plugin}';
    protected $description = 'Creates a new plugin package with automatic integration setup';

    public function handle(): int {
        $packageNameArg = $this->argument('package-name');
        $packageName = strtolower($packageNameArg);
        $nameSpace = '';
        $strings = preg_split('/([-.*\/])/', $packageNameArg);
        $firstCapitals = array_map(ucfirst(...), $strings);
        foreach ($firstCapitals as $item) {
            $nameSpace .= $item;
        }

        $isInDocker = file_exists('/.dockerenv')
            || (file_exists('/proc/1/cgroup') && str_contains(file_get_contents('/proc/1/cgroup'), 'docker'));

        if ($isInDocker) {
            $this->warn('⚠️  It looks like you are running this command inside a Docker container.');
            $this->warn('');
            $this->warn('Package creation requires access to both the frontend and backend code.');
            $this->warn('Running inside the MicroPowerManager backend development container is not supported,');
            $this->warn('because it only has access to the backend source code.');

            if (!$this->confirm('Do you want to proceed anyway?', false)) {
                $this->info('Command aborted.');

                return Command::FAILURE;
            }
        }

        $description = $this->option('description') ?: "This plugin developed for {$nameSpace} functionality.";

        $this->info("Creating package: {$packageName} with namespace: {$nameSpace}");
        $this->info("Description: {$description}");

        // Build the command with proper escaping
        $command = sprintf(
            '%s/../Shell/package-starter.sh %s %s %s',
            __DIR__,
            escapeshellarg($packageName),
            escapeshellarg($nameSpace),
            escapeshellarg($description)
        );

        $this->info('Running package generation script...');
        $output = shell_exec($command);

        if ($output) {
            $this->info($output);
        }

        $this->info('Package generation completed!');

        return Command::SUCCESS;
    }
}
