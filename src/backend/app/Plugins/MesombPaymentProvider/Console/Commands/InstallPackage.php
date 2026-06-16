<?php

namespace App\Plugins\MesombPaymentProvider\Console\Commands;

use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'mesomb-payment-provider:install';
    protected $description = 'Install MesombPaymentProvider Package';

    public function handle(): void {
        $this->info('Installing MesombPaymentProvider Integration Package\n');
        $this->info('Package installed successfully..');
    }
}
