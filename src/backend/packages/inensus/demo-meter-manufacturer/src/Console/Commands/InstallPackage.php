<?php

namespace Inensus\DemoMeterManufacturer\Console\Commands;

use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'demo-meter-manufacturer:install';
    protected $description = 'Install DemoMeterManufacturer Package';

    public function __construct() {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing DemoMeterManufacturer Integration Package\n');

        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\DemoMeterManufacturer\Providers\ServiceProvider",
            '--tag' => 'migrations',
        ]);

        $this->info('Creating database tables\n');
        $this->call('migrate');

        $this->call('plugin:add', [
            'name' => 'DemoMeterManufacturer',
            'composer_name' => 'inensus/demo-meter-manufacturer',
            'description' => 'DemoMeterManufacturer integration package for MicroPowerManager',
        ]);

        $this->info('Package installed successfully..');
    }
}
