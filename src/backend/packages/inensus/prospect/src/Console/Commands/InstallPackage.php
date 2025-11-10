<?php

namespace Inensus\Prospect\Console\Commands;

use Illuminate\Console\Command;
use Inensus\Prospect\Providers\ProspectServiceProvider;

class InstallPackage extends Command {
    protected $signature = 'prospect:install';
    protected $description = 'Install Prospect Package';

    public function handle(): void {
        $this->info('Installing Prospect Integration Package');
        $this->info('Package installed successfully..');
    }
}
