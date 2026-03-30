<?php

namespace App\Plugins\BulkRegistration\Console\Commands;

use App\Plugins\BulkRegistration\Services\MeterTypeService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'bulk-registration:install';
    protected $description = 'Install Bulk Registration Package';

    public function __construct(
        private MeterTypeService $meterTypeService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing BulkRegistration Integration Package\n');

        $this->meterTypeService->createDefaultMeterTypeIfDoesNotExistAny();

        $this->info('Package installed successfully..');
    }
}
