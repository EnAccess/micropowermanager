<?php

namespace App\Plugins\OpenPaygo\Console\Commands;

use App\Plugins\OpenPaygo\Services\ManufacturerService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'openpaygo:install {manufacturer-name} {type : shs or e-bike}';
    protected $description = 'Register a manufacturer that uses the OpenPAYGO token protocol';

    public function __construct(private ManufacturerService $manufacturerService) {
        parent::__construct();
    }

    public function handle(): void {
        $name = $this->argument('manufacturer-name');
        $type = $this->argument('type');

        $this->manufacturerService->register($name, $type);
        $this->info('Manufacturer registered with OpenPaygoApi.');
    }
}
