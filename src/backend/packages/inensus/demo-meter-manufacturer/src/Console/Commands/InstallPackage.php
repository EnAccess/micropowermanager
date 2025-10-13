<?php

namespace Inensus\DemoMeterManufacturer\Console\Commands;

use Illuminate\Console\Command;
use Inensus\DemoMeterManufacturer\Services\ManufacturerService;

class InstallPackage extends Command {
    protected $signature = 'demo-meter-manufacturer:install';
    protected $description = 'Install DemoMeterManufacturer Package';

    public function __construct(
        private ManufacturerService $manufacturerService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing DemoMeterManufacturer Integration Package\n');
        $this->info('Registering manufacturer API\n');
        $this->manufacturerService->register();
        $this->info('Package installed successfully..');
    }
}
