<?php

namespace App\Plugins\SunKingSHS\Console\Commands;

use App\Plugins\SunKingSHS\Services\ManufacturerService;
use App\Plugins\SunKingSHS\Services\SunKingCredentialService;
use Illuminate\Console\Command;

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
