<?php

namespace Inensus\VodacomMobileMoney\Console\Commands;

use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'vodacom-mobile-money:install';
    protected $description = 'Install VodacomMobileMoney Package';

    public function __construct() {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing VodacomMobileMoney Integration Package\n');

        $this->info('Package installed successfully..');
    }
}
