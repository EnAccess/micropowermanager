<?php

namespace App\Plugins\OdysseyDataExport\Console\Commands;

use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'odyssey-data-export:install';
    protected $description = 'Install OdysseyDataExport Package';

    public function handle(): void {
        $this->info('Installing OdysseyDataExport Integration Package\n');
        $this->info('Package installed successfully..');
    }
}
