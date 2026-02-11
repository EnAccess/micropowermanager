<?php

namespace App\Plugins\SparkShs\Console\Commands;

use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'spark-shs:install';
    protected $description = 'Install SparkShs Package';

    public function __construct() {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing SparkShs Integration Package\n');

        // Here you can add plugin initialisation code.
        // For example creating initial plugin credentials in the database
        // or registering a Manufacurer with MicroPowerManager.

        $this->info('Package installed successfully..');
    }
}
