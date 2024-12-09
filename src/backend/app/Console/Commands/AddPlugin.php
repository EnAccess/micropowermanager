<?php

namespace App\Console\Commands;

use App\Services\PluginsService;

class AddPlugin extends AbstractSharedCommand {
    protected $signature = 'plugin:add {name} {composer_name} {description}';
    protected $description = 'Plugin Details adding to database';

    public function __construct(private PluginsService $pluginsService) {
        parent::__construct();
    }

    public function runInCompanyScope(): void {
        $name = $this->argument('name');
        $composer_name = $this->argument('composer_name');
        $description = $this->argument('description');

        $this->pluginsService->addPlugin($name, $composer_name, $description);
    }
}
