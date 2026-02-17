<?php
namespace App\Plugins\{{Plugin-Name}}\Console\Commands;

use Illuminate\Console\Command;

class InstallPackage extends Command
{
    protected $signature = '{{plugin-name}}:install';
    protected $description = 'Install {{Plugin-Name}} Package';

    public function __construct() {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Installing {{Plugin-Name}} Integration Package\n');

        // Here you can add plugin initialisation code.
        // For example creating initial plugin credentials in the database
        // or registering a Manufacurer with MicroPowerManager.

        $this->info('Package installed successfully..');
    }
}
