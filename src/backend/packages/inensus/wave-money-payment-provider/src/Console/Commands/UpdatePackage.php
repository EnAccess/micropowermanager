<?php

namespace Inensus\WaveMoneyPaymentProvider\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Inensus\WaveMoneyPaymentProvider\Services\WaveMoneyCredentialService;

class UpdatePackage extends Command {
    protected $signature = 'wave-money-payment-provider:update';
    protected $description = 'Update WaveMoney Package';

    public function handle(): void {
        $this->info('WaveMoney Integration Updating Started\n');
        $this->info('Package updated successfully..');
    }
}
