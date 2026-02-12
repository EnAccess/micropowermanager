<?php

namespace App\Plugins\SparkShs\Console\Commands;

use App\Plugins\SparkShs\Services\ManufacturerService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'spark-shs:install';
    protected $description = 'Install SparkShs Package';

    public function __construct(
        private ManufacturerService $manufacturerService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing SparkShs Integration Package\n');

        $this->manufacturerService->register();

        $this->info('Package installed successfully..');
    }
}
