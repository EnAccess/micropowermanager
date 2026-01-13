<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PluginGenerator extends Command {
    protected $signature = 'micropowermanager:new-plugin {plugin-name} {--description= : Optional description for the plugin}';
    protected $description = 'Creates a new MicroPowerManager plugin';

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
        $pluginNameArg = $this->argument('plugin-name');
        $pluginName = strtolower($pluginNameArg);
        $nameSpace = '';
        $strings = preg_split('/([-.*\/])/', $pluginNameArg);
        $firstCapitals = array_map(ucfirst(...), $strings);
        foreach ($firstCapitals as $item) {
            $nameSpace .= $item;
        }

        $isInDocker = file_exists('/.dockerenv')
            || (file_exists('/proc/1/cgroup') && str_contains(file_get_contents('/proc/1/cgroup'), 'docker'));

        if ($isInDocker) {
            $this->outputComponents()->warn(
                'It looks like you are running this command inside a Docker container.'
            );
            $this->outputComponents()->info(
                'Plugin creation requires access to both the frontend and backend code.
                Running inside the MicroPowerManager backend development container is not supported,
                because it only has access to the backend source code.'
            );

            if (!$this->outputComponents()->confirm('Do you want to proceed anyway?', false)) {
                $this->outputComponents()->error('Command aborted.');

                return Command::FAILURE;
            }
        }

        $description = $this->option('description') ?: "This plugin adds {$nameSpace} functionality to MicroPowerManager.";

        $this->outputComponents()->info('Creating plugin with following information:');

        $this->outputComponents()->twoColumnDetail('Plugin name:', $pluginName);
        $this->outputComponents()->twoColumnDetail('Plugin namespace:', $nameSpace);
        $this->outputComponents()->twoColumnDetail('Description:', $description);

        $this->outputComponents()->info('Running plugin generation script...');

        // Determine project root
        $projectRoot = File::isDirectory('/var/www/html') ? '/var/www/html' : base_path();

        // Get next plugin ID
        $mpmPluginPath = "{$projectRoot}/app/Models/MpmPlugin.php";
        $content = File::get($mpmPluginPath);
        preg_match_all('/public const .*?= (\d+);/', $content, $matches);
        $currentMaxId = collect($matches[1])->map(fn ($id): int => (int) $id)->max();
        $nextPluginId = $currentMaxId + 1;

        $this->outputComponents()->info("Next available plugin ID: {$nextPluginId}");

        // Step 1: Clone backend template
        $pluginPath = "{$projectRoot}/app/Plugins/{$pluginName}";
        $sourceTemplate = implode(
            DIRECTORY_SEPARATOR,
            [dirname($projectRoot), 'plugin-template', 'backend']
        );

        if (!File::exists($sourceTemplate)) {
            $this->outputComponents()->error("Template not found at: {$sourceTemplate}");

            return 1;
        }

        File::copyDirectory($sourceTemplate, $pluginPath);

        // Step 2: Update InstallPackage CLI command
        $this->replaceInFile("{$pluginPath}/Console/Commands/InstallPackage.php", [
            '{{Plugin-Name}}' => $nameSpace,
            '{{plugin-name}}' => $pluginName,
        ]);

        // Step 3: Update Providers
        $providersDir = "{$pluginPath}/Providers";
        foreach (['EventServiceProvider', 'ObserverServiceProvider', 'RouteServiceProvider'] as $file) {
            $this->replaceInFile("{$providersDir}/{$file}.php", [
                '{{Plugin-Name}}' => $nameSpace,
                '{{plugin-name}}' => $pluginName,
            ]);
        }

        // Rename and update main provider
        $mainProviderOld = "{$providersDir}/{{Plugin-Name}}ServiceProvider.php";
        $mainProviderNew = "{$providersDir}/{$nameSpace}ServiceProvider.php";
        File::move($mainProviderOld, $mainProviderNew);

        $this->replaceInFile($mainProviderNew, [
            '{{Plugin-Name}}' => $nameSpace,
            '{{plugin-name}}' => $pluginName,
        ]);

        // Add namespace declaration
        $providerContent = File::get($mainProviderNew);
        $providerContent = preg_replace(
            '/^<\?php\s*/',
            "<?php\n\nnamespace Inensus\\{$nameSpace}\\Providers;\n\n",
            $providerContent
        );
        File::put($mainProviderNew, $providerContent);

        // Step 4: Update routes
        $routesDir = "{$pluginPath}/routes";
        foreach (['api'] as $file) {
            $this->replaceInFile("{$routesDir}/{$file}.php", [
                '{{Plugin-Name}}' => $nameSpace,
                '{{plugin-name}}' => $pluginName,
            ]);
        }

        // Step 5: Update frontend and frontend routes
        $projectRootFrontend = implode(
            DIRECTORY_SEPARATOR,
            [dirname(base_path()), 'frontend']
        );
        $pluginPathFrontend = implode(
            DIRECTORY_SEPARATOR,
            [$projectRootFrontend, 'src', 'plugins', $pluginName]
        );
        $sourceTemplateFrontend = implode(
            DIRECTORY_SEPARATOR,
            [dirname($projectRootFrontend), 'plugin-template', 'frontend']
        );

        File::copyDirectory($sourceTemplateFrontend, $pluginPathFrontend);

        // Step 7: Register provider
        $this->outputComponents()->info('Registering ServiceProvider in bootstrap/providers.php...');
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
        $this->outputComponents()->info('Adding plugin constant to MpmPlugin model...');
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
        $this->outputComponents()->info('Generating database migration for plugin registration...');
        $timestamp = now()->format('Y_m_d_His');
        $migrationName = "add_{$pluginName}_to_mpm_plugin_table";
        $migrationTemplateDir = implode(
            DIRECTORY_SEPARATOR,
            [dirname($projectRoot), 'plugin-template', 'migrations']
        );
        $migrationFile = "{$projectRoot}/database/migrations/{$timestamp}_{$migrationName}.php";

        // Create the migration file directly since make:migration is causing issues
        $this->outputComponents()->info("Creating migration file: $migrationFile");
        File::copy(
            "{$migrationTemplateDir}/add_{{plugin_name}}_to_mpm_plugin_table.php",
            $migrationFile
        );
        $this->replaceInFile($migrationFile, [
            '{{constantName}}' => $constantName,
            '{{description}}' => $description,
            '{{Plugin-Name}}' => $nameSpace,
            '{{plugin-name}}' => $pluginName,
        ]);
        $this->outputComponents()->success('Migration file created successfully!');

        // Step 11: Run composer dump-autoload
        $this->outputComponents()->info('Running composer dump-autoload...');
        exec('composer dump-autoload', $output, $resultCode);

        if ($resultCode !== 0) {
            $this->outputComponents()->warn('Composer not found in PATH, trying php composer.phar dump-autoload...');
            exec('php composer.phar dump-autoload', $output, $resultCode);
        }

        if ($resultCode === 0) {
            $this->outputComponents()->success('Composer autoload dump complete.');
        } else {
            $this->outputComponents()->error('Failed to run composer dump-autoload.');
        }

        $this->outputComponents()->success("Plugin '{$pluginName}' created successfully!");

        $this->outputComponents()->line('info', 'Next steps:');
        $this->outputComponents()->bulletList([
            "Review the generated files in app/Plugins/{$pluginName}",
            // "Move the UI folder to src/frontend/src/plugins/{$pluginName}",
            // 'Add frontend routes to src/frontend/src/ExportedRoutes.js',
            'Run migration: php artisan migrate',
            // "Install plugin: php artisan {$pluginName}:install",
        ]);

        return Command::SUCCESS;
    }
}
