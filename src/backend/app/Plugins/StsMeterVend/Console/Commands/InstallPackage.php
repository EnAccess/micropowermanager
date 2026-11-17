<?php

namespace App\Plugins\StsMeterVend\Console\Commands;

use App\Models\MpmPlugin;
use App\Services\MpmPluginService;
use App\Services\RegistrationTailService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'sts-meter-vend:install';
    protected $description = 'Install StsMeterVend Package';

    public function __construct(
        private MpmPluginService $mpmPluginService,
        private RegistrationTailService $registrationTailService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing StsMeterVend Integration Package');
        // The engine URL is deployment configuration and decoder keys are imported as an
        // ongoing task, so there is no one-time credential step for the wizard to collect.
        $this->registrationTailService->removeMpmPluginFromRegistrationTail(
            $this->mpmPluginService->getById(MpmPlugin::STS_METER_VEND),
        );
        $this->info('Package installed successfully..');
    }
}
