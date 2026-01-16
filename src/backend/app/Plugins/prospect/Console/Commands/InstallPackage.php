<?php

namespace App\Plugins\Prospect\Console\Commands;

use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'prospect:install';
    protected $description = 'Install Prospect Package';

    public function handle(): void {
        $this->info('Installing Prospect Integration Package');
        $this->info('Package installed successfully..');
    }
}
