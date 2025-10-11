<?php

namespace Inensus\DemoShsManufacturer\Console\Commands;

use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'demo-shs-manufacturer:install';
    protected $description = 'Install DemoShsManufacturer Package';

    public function __construct() {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing DemoShsManufacturer Integration Package\n');

        $this->info('Copying migrations\n');
        $this->call('vendor:publish', [
            '--provider' => "Inensus\DemoShsManufacturer\Providers\ServiceProvider",
            '--tag' => 'migrations',
        ]);

        $this->info('Creating database tables\n');
        $this->call('migrate');

        $this->call('plugin:add', [
            'name' => 'DemoShsManufacturer',
            'composer_name' => 'inensus/demo-shs-manufacturer',
            'description' => 'DemoShsManufacturer integration package for MicroPowerManager',
        ]);

        $this->info('Package installed successfully..');
    }
}
