<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class PackageGenerator extends Command {
    protected $signature = 'micropowermanager:new-package {package-name} {--description= : Optional description for the plugin}';
    protected $description = 'Creates a new plugin package with automatic integration setup';

    public function handle(): void {
        $packageNameArg = $this->argument('package-name');
        $packageName = strtolower($packageNameArg);
        $nameSpace = '';
        $strings = preg_split('/([-.*\/])/', $packageNameArg);
        $firstCapitals = array_map(ucfirst(...), $strings);
        foreach ($firstCapitals as $item) {
            $nameSpace .= $item;
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
    }
}
