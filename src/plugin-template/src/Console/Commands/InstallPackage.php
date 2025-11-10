<?php
namespace Inensus\{{Package-Name}}\Console\Commands;

use Illuminate\Console\Command;

class InstallPackage extends Command
{
    protected $signature = '{{package-name}}:install';
    protected $description = 'Install {{Package-Name}} Package';

    public function handle(): void
    {
        $this->info('Installing {{Package-Name}} Integration Package\n');

        $this->call('plugin:add', [
            'name' => "{{Package-Name}}",
            'composer_name' => "inensus/{{package-name}}",
            'description' => "{{Package-Name}} integration package for MicroPowerManager",
        ]);
        $this->call('routes:generate');

        $this->info('Package installed successfully..');
    }
}
