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
        $this->info('Installing Prospect Integration Package');
        $this->call('vendor:publish', ['--provider' => "Inensus\Prospect\Providers\ProspectServiceProvider", '--tag' => "migrations"]);
        $this->call('migrate');
        $this->info('Package installed successfully..');
    }
}
