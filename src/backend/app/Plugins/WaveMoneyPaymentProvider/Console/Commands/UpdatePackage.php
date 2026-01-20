<?php

namespace App\Plugins\WaveMoneyPaymentProvider\Console\Commands;

use Illuminate\Console\Command;

class UpdatePackage extends Command {
    protected $signature = 'wave-money-payment-provider:update';
    protected $description = 'Update WaveMoney Package';

    public function handle(): void {
        $this->info('WaveMoney Integration Updating Started\n');
        $this->info('Package updated successfully..');
    }
}
