<?php

namespace App\Plugins\DemoMeterManufacturer\Console\Commands;

use App\Plugins\DemoMeterManufacturer\Services\ManufacturerService;
use Illuminate\Console\Command;

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
