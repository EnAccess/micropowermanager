<?php

declare(strict_types=1);

namespace App\Plugins\WhatsappBusinessApi\Console\Commands;

use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'WhatsappBusinessApi:install';
    protected $description = 'Install WhatsappBusinessApi Package';

    public function handle(): void {
        $this->info("Installing WhatsappBusinessApi Integration Package\n");
        $this->info('Package installed successfully..');
    }
}
