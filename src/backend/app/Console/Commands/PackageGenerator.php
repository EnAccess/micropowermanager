<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PackageGenerator extends Command {
    protected $signature = 'micropowermanager:new-package {package-name} {--description= : Optional description for the plugin}';
    protected $description = 'Creates a new plugin package with automatic integration setup';

    /**
     * Helper function to replace placeholders in files similar to `sed`.
     *
     * @param array<string, string> $replacements
     */
    public function replaceInFile(string $path, array $replacements): void {
        $content = File::get($path);
        foreach ($replacements as $search => $replace) {
            $content = str_replace($search, $replace, $content);
        }
        File::put($path, $content);
    }

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

        $this->info('Running package generation script...');

        // Determine project root
        $projectRoot = File::isDirectory('/var/www/html') ? '/var/www/html' : base_path();

        // Get next plugin ID
        $mpmPluginPath = "{$projectRoot}/app/Models/MpmPlugin.php";
        $content = File::get($mpmPluginPath);
        preg_match_all('/public const .*?= (\d+);/', $content, $matches);
        $currentMaxId = collect($matches[1])->map(fn ($id): int => (int) $id)->max();
        $nextPluginId = $currentMaxId + 1;

        $this->info("Next available plugin ID: {$nextPluginId}");

        // Clone template
        $packagePath = "{$projectRoot}/packages/inensus/{$packageName}";
        $sourceTemplate = dirname($projectRoot).DIRECTORY_SEPARATOR.'plugin-template';

        if (!File::exists($sourceTemplate)) {
            $this->error("Template not found at: {$sourceTemplate}");

            return 1;
        }

        File::copyDirectory($sourceTemplate, $packagePath);

        // Step 2: Update InstallPackage.php
        $this->replaceInFile("{$packagePath}/src/Console/Commands/InstallPackage.php", [
            '{{Package-Name}}' => $nameSpace,
            '{{package-name}}' => $packageName,
        ]);

        // Step 3: Providers
        $providersDir = "{$packagePath}/src/Providers";
        foreach (['EventServiceProvider', 'ObserverServiceProvider', 'RouteServiceProvider'] as $file) {
            $this->replaceInFile("{$providersDir}/{$file}.php", ['{{Package-Name}}' => $nameSpace]);
        }

        // Rename and update main provider
        $mainProviderOld = "{$providersDir}/{{Package-Name}}ServiceProvider.php";
        $mainProviderNew = "{$providersDir}/{$nameSpace}ServiceProvider.php";
        File::move($mainProviderOld, $mainProviderNew);

        $this->replaceInFile($mainProviderNew, [
            '{{Package-Name}}' => $nameSpace,
            '{{package-name}}' => $packageName,
        ]);

        // Add namespace declaration
        $providerContent = File::get($mainProviderNew);
        $providerContent = preg_replace(
            '/^<\?php\s*/',
            "<?php\n\nnamespace Inensus\\{$nameSpace}\\Providers;\n\n",
            $providerContent
        );
        File::put($mainProviderNew, $providerContent);

        // Step 4: Update frontend routes
        $this->replaceInFile("{$packagePath}/src/resources/assets/js/routes.js", [
            '{{package-name}}' => $packageName,
        ]);

        // Step 6: Update composer.json inside the plugin
        $this->replaceInFile("{$packagePath}/composer.json", [
            '{{Package-Name}}' => $nameSpace,
            '{{package-name}}' => $packageName,
        ]);

        // Step 7: Register provider
        $this->info('Registering ServiceProvider in bootstrap/providers.php...');
        $providersFile = "{$projectRoot}/bootstrap/providers.php";
        $providersContent = File::get($providersFile);

        $useStatement = "use Inensus\\{$nameSpace}\\Providers\\{$nameSpace}ServiceProvider;";
        if (!str_contains($providersContent, $useStatement)) {
            $providersContent = preg_replace(
                '/(use Inensus.*ServiceProvider;)(?!.*use Inensus)/s',
                "$1\n{$useStatement}",
                $providersContent
            );
        }

        // Add to providers array before closing bracket
        $providersContent = preg_replace(
            '/];\s*$/m',
            "    {$nameSpace}ServiceProvider::class,\n];",
            $providersContent
        );

        File::put($providersFile, $providersContent);

        // Step 9: Add plugin constant
        $this->info('Adding plugin constant to MpmPlugin model...');
        $constantName = strtoupper(ltrim(preg_replace('/([A-Z])/', '_$1', $nameSpace), '_'));
        $lines = explode("\n", $content);
        $lastConst = null;
        foreach ($lines as $i => $line) {
            if (preg_match('/public const .*?= \d+;/', $line)) {
                $lastConst = $i;
            }
        }
        if ($lastConst !== null) {
            array_splice($lines, $lastConst + 1, 0, "    public const {$constantName} = {$nextPluginId};");
            File::put($mpmPluginPath, implode("\n", $lines));
        }

        // Step 10: Create migration
        $this->info('Generating database migration for plugin registration...');
        $timestamp = now()->format('Y_m_d_His');
        $migrationName = "add_{$packageName}_to_mpm_plugin_table";
        $migrationSourceDir = "{$packagePath}/database/migrations";
        $migrationFile = "{$projectRoot}/database/migrations/{$timestamp}_{$migrationName}.php";

        // Create the migration file directly since make:migration is causing issues
        $this->info("Creating migration file: $migrationFile");
        File::move("{$migrationSourceDir}/add_{{package_name}}_to_mpm_plugin_table.php", $migrationFile);
        $this->replaceInFile($migrationFile, [
            '{{constantName}}' => $constantName,
            '{{description}}' => $description,
            '{{Package-Name}}' => $nameSpace,
            '{{package-name}}' => $packageName,
        ]);
        $this->info('Migration file created successfully!');

        // Step 11: Run composer dump-autoload
        $this->info('Running composer dump-autoload...');
        exec('composer dump-autoload', $output, $resultCode);

        if ($resultCode !== 0) {
            $this->warn('Composer not found in PATH, trying php composer.phar dump-autoload...');
            exec('php composer.phar dump-autoload', $output, $resultCode);
        }

        if ($resultCode === 0) {
            $this->info('Composer autoload dump complete.');
        } else {
            $this->error('Failed to run composer dump-autoload.');
        }

        $this->info("\n==================================================");
        $this->info("Package '{$packageName}' created successfully!");
        $this->info('==================================================');
        $this->line('Next steps:');
        $this->line("1. Review the generated files in packages/inensus/{$packageName}");
        $this->line("2. Move the UI folder to src/frontend/src/plugins/{$packageName}");
        $this->line('3. Add frontend routes to src/frontend/src/ExportedRoutes.js');
        $this->line('4. Run migration: php artisan migrate');
        $this->line("5. Install plugin: php artisan {$packageName}:install");
        $this->info('==================================================');

        $this->info('Package generation completed!');

        return Command::SUCCESS;
    }
}
