<?php

namespace Inensus\DemoShsManufacturer\Console\Commands;

use Illuminate\Console\Command;
use Inensus\DemoShsManufacturer\Services\ManufacturerService;

class InstallPackage extends Command {
    protected $signature = 'demo-shs-manufacturer:install';
    protected $description = 'Install DemoShsManufacturer Package';

    public function __construct(
        private ManufacturerService $manufacturerService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing DemoShsManufacturer Integration Package\n');
        $this->info('Registering manufacturer API\n');
        $this->manufacturerService->register();
        $this->info('Package installed successfully..');
    }
}
