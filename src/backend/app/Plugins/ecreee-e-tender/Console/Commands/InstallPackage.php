<?php

namespace Inensus\EcreeeETender\Console\Commands;

use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'ecreee-e-tender:install';
    protected $description = 'Install Ecreee e-tender Package';

    public function __construct(
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing Ecreee e-tender Integration Package\n');
        $this->info('Package installed successfully..');
    }
}
