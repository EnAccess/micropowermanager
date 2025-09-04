<?php

namespace Inensus\SunKingSHS\Console\Commands;

use Illuminate\Console\Command;
use Inensus\SunKingSHS\Services\ManufacturerService;
use Inensus\SunKingSHS\Services\SunKingCredentialService;

class InstallPackage extends Command {
    protected $signature = 'sun-king-shs:install';
    protected $description = 'Install SunKingSHS Package';

    public function __construct(
        private ManufacturerService $manufacturerService,
        private SunKingCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing SunKingSHS Integration Package\n');

        $this->manufacturerService->register();
        $this->credentialService->createCredentials();
        $this->info('Package installed successfully..');
    }
}
