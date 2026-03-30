<?php

namespace App\Plugins\DemoShsManufacturer\Console\Commands;

use App\Plugins\DemoShsManufacturer\Services\ManufacturerService;
use Illuminate\Console\Command;

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
