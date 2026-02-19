<?php

namespace App\Plugins\SparkShs\Console\Commands;

use App\Plugins\SparkShs\Services\ManufacturerService;
use App\Plugins\SparkShs\Services\SparkShsCredentialService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'spark-shs:install';
    protected $description = 'Install SparkShs Package';

    public function __construct(
        private ManufacturerService $manufacturerService,
        private SparkShsCredentialService $sparkShsCredetialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing SparkShs Integration Package\n');

        $this->manufacturerService->register();
        $this->sparkShsCredetialService->getCredentials();

        $this->info('Package installed successfully..');
    }
}
