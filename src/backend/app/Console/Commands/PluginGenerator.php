<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PluginGenerator extends Command {
    protected $signature = 'micropowermanager:new-plugin
        {plugin-name : Name of the new plugin. Use kebab-case (words separated by hyphens), e.g. `my-new-plugin`}
        {--description= : Optional description for the plugin}
        {--docs-category= : Optional folder in `docs/integrations-guide` the plugin documentation is created in. Guessed from the plugin name if omitted}';
    protected $description = 'Creates a new MicroPowerManager plugin';

    private const string DEFAULT_DOCS_CATEGORY = 'manufacturer';

    private const array DOCS_CATEGORY_PATTERNS = [
        'manufacturer' => '/meter|shs|solar|bike|oem|manufacturer|bms|inverter|paygo/',
        'payment-provider' => '/payment|money|pesa/',
        'communication' => '/sms|message|messaging/',
        'reporting' => '/import|export|data|analytics|report/',
    ];

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

    private function guessDocsCategory(string $pluginName): string {
        foreach (self::DOCS_CATEGORY_PATTERNS as $docsCategory => $pattern) {
            if (preg_match($pattern, $pluginName) === 1) {
                return $docsCategory;
            }
        }

        return self::DEFAULT_DOCS_CATEGORY;
    }

    public function handle(): int {
        $pluginName = strtolower($this->argument('plugin-name'));
        $pluginNameStrings = preg_split('/([-.*\/])/', $pluginName);

        $firstCapitals = array_map(ucfirst(...), $pluginNameStrings);
        $nameSpace = implode('', $firstCapitals);
        $pluginNameHumanReadable = implode(' ', $firstCapitals);

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

        // Determine root directories
        // Assume this script is called from `src/backend`
        $projectRootBackend = base_path();
        $projectRootFrontend = implode(DIRECTORY_SEPARATOR, [dirname($projectRootBackend), 'frontend']);
        $projectRoot = dirname($projectRootBackend, 2);

        $description = $this->option('description') ?: "This plugin adds {$nameSpace} functionality to MicroPowerManager.";
        $docsCategory = $this->option('docs-category') ?: $this->guessDocsCategory($pluginName);
        $docsCategoryPath = implode(DIRECTORY_SEPARATOR, [$projectRoot, 'docs', 'integrations-guide', $docsCategory]);

        if (!File::isDirectory($docsCategoryPath)) {
            $this->outputComponents()->error("Docs category not found at: {$docsCategoryPath}");

            return Command::FAILURE;
        }

        $this->outputComponents()->info('Creating plugin with following information:');

        $this->outputComponents()->twoColumnDetail('Plugin name:', $pluginName);
        $this->outputComponents()->twoColumnDetail('Plugin namespace:', $nameSpace);
        $this->outputComponents()->twoColumnDetail('Description:', $description);
        $this->outputComponents()->twoColumnDetail('Docs category:', $docsCategory);

        $this->outputComponents()->info('Running plugin generation script...');

        $pluginSlug = str_replace('-', '_', $pluginName);

        // Quoted placeholders sit in PHP string literals, so they are replaced first and escaped.
        $replacements = [
            "'{{Plugin Name}}'" => var_export($pluginNameHumanReadable, true),
            "'{{description}}'" => var_export($description, true),
            '{{description}}' => $description,
            '{{Plugin-Name}}' => $nameSpace,
            '{{Plugin Name}}' => $pluginNameHumanReadable,
            '{{plugin-name}}' => $pluginName,
            '{{plugin_slug}}' => $pluginSlug,
        ];

        // Step 1: Clone the plugin template into the correct directories and populate placeholders

        //
        // backend
        //

        // copy template
        $pluginPathBackend = implode(
            DIRECTORY_SEPARATOR,
            [$projectRootBackend, 'app', 'Plugins', $nameSpace]
        );
        $sourceTemplateBackend = implode(
            DIRECTORY_SEPARATOR,
            [$projectRoot, 'src', 'plugin-template', 'backend']
        );

        if (!File::exists($sourceTemplateBackend)) {
            $this->outputComponents()->error("Template not found at: {$sourceTemplateBackend}");

            return Command::FAILURE;
        }

        File::copyDirectory($sourceTemplateBackend, $pluginPathBackend);

        // Rename main provider
        $mainProviderOld = "{$pluginPathBackend}/Providers/{{Plugin-Name}}ServiceProvider.php";
        $mainProviderNew = "{$pluginPathBackend}/Providers/{$nameSpace}ServiceProvider.php";
        File::move($mainProviderOld, $mainProviderNew);

        // populate placeholders
        foreach (File::allFiles($pluginPathBackend) as $file) {
            $this->replaceInFile($file->getPathname(), $replacements);
        }

        // done
        $this->outputComponents()->success("Backend plugin template created at {$pluginPathBackend}.");

        //
        // frontend
        //

        // copy template
        $pluginPathFrontend = implode(
            DIRECTORY_SEPARATOR,
            [$projectRootFrontend, 'src', 'plugins', $pluginName]
        );
        $sourceTemplateFrontend = implode(
            DIRECTORY_SEPARATOR,
            [$projectRoot, 'src', 'plugin-template',  'frontend']
        );

        if (!File::exists($sourceTemplateFrontend)) {
            $this->outputComponents()->error("Template not found at: {$sourceTemplateFrontend}");

            return Command::FAILURE;
        }

        File::copyDirectory($sourceTemplateFrontend, $pluginPathFrontend);

        // populate placeholders
        foreach (File::allFiles($pluginPathFrontend) as $file) {
            $this->replaceInFile($file->getPathname(), $replacements);
        }

        // done
        $this->outputComponents()->success("Frontend plugin template created at {$pluginPathFrontend}.");

        //
        // documentation
        //

        // copy template
        $pluginDocsFile = "{$docsCategoryPath}/{$pluginName}.md";

        File::copy(
            "{$projectRoot}/src/plugin-template/docs/{{plugin-name}}.md",
            $pluginDocsFile
        );

        // populate placeholders
        $this->replaceInFile($pluginDocsFile, $replacements);

        // done
        $this->outputComponents()->success("Plugin documentation created at {$pluginDocsFile}.");

        // Step 2: Register new plugin in various places in MicroPowerManager

        // Step 2.1: Register plugin provider with Laravel
        $this->outputComponents()->info('Registering ServiceProvider in bootstrap/providers.php...');
        $providersFile = "{$projectRootBackend}/bootstrap/providers.php";
        $providersContent = File::get($providersFile);

        $useStatement = "use App\Plugins\\{$nameSpace}\\Providers\\{$nameSpace}ServiceProvider;";
        if (!str_contains($providersContent, $useStatement)) {
            $providersContent = preg_replace(
                '/(use App\\\Plugins\\\\\S*ServiceProvider;)(?!.*use App\\\Plugins\\\)/s',
                "$1\n{$useStatement}",
                $providersContent
            );
        }

        // Add to providers array before closing bracket
        $providersContent = preg_replace(
            '/];\s*$/m',
            "    {$nameSpace}ServiceProvider::class,\n];\n",
            $providersContent
        );

        File::put($providersFile, $providersContent);

        $this->outputComponents()->success("Plugin provider {$nameSpace}ServiceProvider added to {$providersFile}.");

        // Step 2.2: Add plugin UI component to ExportedRoutes.js (this generates the sidebar entry)
        $exportedRoutesFile = "{$projectRootFrontend}/src/ExportedRoutes.js";
        $exportedRoutesFileContent = File::get($exportedRoutesFile);

        $importStatement = "import {$nameSpace}Overview from \"@/plugins/{$pluginName}/modules/Overview/Overview.vue\"";
        if (!str_contains($exportedRoutesFileContent, $importStatement)) {
            $exportedRoutesFileContent = preg_replace(
                '/(import.*from "@\\/plugins\\/\\S*)(?!import.*)/s',
                "$1\n{$importStatement}",
                $exportedRoutesFileContent
            );
        }

        $exportedRoutesSnippet = <<<JS
{
    path: "/{$pluginName}",
    component: ChildRouteWrapper,
    meta: {
      sidebar: {
        enabled_by_mpm_plugin: "{$pluginSlug}",
        name: "{$pluginNameHumanReadable}",
        icon: "cloud_upload",
      },
    },
    children: [
      {
        path: "overview",
        component: {$nameSpace}Overview,
        meta: {
          layout: "default",
          sidebar: {
            enabled: true,
            name: "Overview",
          },
        },
      },
    ],
  },
JS;

        $exportedRoutesFileContent = preg_replace(
            '/^(\s*)(\/\/ NEW PLUGIN PLACEHOLDER \(DO NOT REMOVE THIS LINE\))/m',
            '$1'.$exportedRoutesSnippet."\n".'$1$2',
            $exportedRoutesFileContent,
            1,
        );

        File::put($exportedRoutesFile, $exportedRoutesFileContent);

        $this->outputComponents()->success("Plugin frontend routes added to {$exportedRoutesFile}.");

        // Step 2.3: Export a global component to be displayed in
        $mainJsFile = "{$projectRootFrontend}/src/main.js";
        $mainJsFileFileContent = File::get($mainJsFile);

        $mainJsImportStatement = "import {$nameSpace}Overview from \"@/plugins/{$pluginName}/modules/Overview/Overview.vue\"";
        if (!str_contains($mainJsFileFileContent, $mainJsImportStatement)) {
            $mainJsFileFileContent = preg_replace(
                '/(import.*from "@\\/plugins\\/\\S*)(?!import.*)/s',
                "$1\n{$mainJsImportStatement}",
                $mainJsFileFileContent
            );
        }

        $mainJsSnippet = "Vue.component(\"{$pluginSlug}\", {$nameSpace}Overview)";
        $mainJsFileFileContent = preg_replace(
            '/^(\s*)(\/\/ NEW PLUGIN PLACEHOLDER \(DO NOT REMOVE THIS LINE\))/m',
            '$1'.$mainJsSnippet."\n".'$1$2',
            $mainJsFileFileContent,
            1,
        );

        File::put($mainJsFile, $mainJsFileFileContent);

        $this->outputComponents()->success("Global Overview component added to {$mainJsFile}.");

        // Step 3: As a final step, run composer dump-autoload
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
            "Review the generated backend files in {$pluginPathBackend}",
            "Review the generated frontend files in {$pluginPathFrontend}",
            "Document your plugin in {$pluginDocsFile}",
            'Run migration: `php artisan migrate`',
            'Start developing your new plugin. See the Plugin Development guide for more! https://micropowermanager.io/development/plugins.html',
        ]);

        return Command::SUCCESS;
    }
}
