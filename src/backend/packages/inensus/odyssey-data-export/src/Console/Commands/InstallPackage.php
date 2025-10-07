<?php

namespace Inensus\OdysseyDataExport\Console\Commands;

use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'odyssey-data-export:install';
    protected $description = 'Install OdysseyDataExport Package';

    public function __construct() {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing OdysseyDataExport Integration Package\n');

        $this->call('plugin:add', [
            'name' => 'OdysseyDataExport',
            'composer_name' => 'inensus/odyssey-data-export',
            'description' => 'OdysseyDataExport integration package for MicroPowerManager',
        ]);
        $this->call('routes:generate');
        $this->info('Package installed successfully..');
    }
}
