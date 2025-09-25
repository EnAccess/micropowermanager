<?php

namespace Inensus\Prospect\Console\Commands;

use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'prospect:install';
    protected $description = 'Install Prospect Package';

    public function __construct() {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing Prospect Integration Package\n');

        $this->info('Package installed successfully..');
    }
}
