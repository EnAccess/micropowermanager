<?php

namespace Inensus\WavecomPaymentProvider\Console\Commands;

use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'wavecom-payment-provider:install';
    protected $description = 'Install wavecom-money-payment-provider Package';

    public function handle(): void {
        $this->info('Installing WavecomPaymentProvider Integration Package\n');
        $this->info('Package installed successfully..');
    }
}
