<?php
namespace Inensus\{{Package-Name}}\Console\Commands;

use Illuminate\Console\Command;

class InstallPackage extends Command
{
    protected $signature = '{{package-name}}:install';
    protected $description = 'Install {{Package-Name}} Package';

    public function __construct() {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Installing {{Package-Name}} Integration Package\n');

        // Here you can add plugin initialisation code.
        // For example creating initial plugin credentials in the database
        // or registering a Manufacurer with MicroPowerManager.

        $this->info('Package installed successfully..');
    }
}
