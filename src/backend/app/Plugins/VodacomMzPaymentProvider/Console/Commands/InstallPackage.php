<?php

namespace App\Plugins\VodacomMzPaymentProvider\Console\Commands;

use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'vodacom-mobile-money:install';
    protected $description = 'Install VodacomMzPaymentProvider Package';

    public function __construct() {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing VodacomMzPaymentProvider Integration Package\n');

        $this->info('Package installed successfully..');
    }
}
