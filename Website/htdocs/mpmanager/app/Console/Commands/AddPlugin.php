<?php

namespace App\Console\Commands;

use App\Services\PluginsService;
use Illuminate\Console\Command;

class AddPlugin extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'plugin:add {name} {composer_name} {description}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Plugin Details adding to database';
    /**
     * @var PluginsService
     */
    private $pluginService;

    /**
     * Create a new command instance.
     *
     * @param PluginsService $pluginsService
     */
    public function __construct(PluginsService $pluginsService)
    {
        parent::__construct();
        $this->pluginService = $pluginsService;
    }

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $name = $this->argument('name');
        $composer_name = $this->argument('composer_name');
        $description = $this->argument('description');

        $this->pluginService->addPlugin($name, $composer_name, $description);
    }
}
