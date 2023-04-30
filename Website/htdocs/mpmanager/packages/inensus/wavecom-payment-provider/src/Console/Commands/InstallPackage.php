<?php

namespace Inensus\WavecomPaymentProvider\Console\Commands;

use Illuminate\Console\Command;
use Inensus\WavecomPaymentProvider\Services\MenuItemService;

class InstallPackage extends Command
{
    protected $signature = 'wavecom-payment-provider:install';
    protected $description = 'Install wavecom-money-payment-provider Package';


    public function __construct(private MenuItemService $menuItemService)
    {

        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Installing WavecomPaymentProvider Integration Package\n');
        $this->menuItemService->createMenuItems();
        $this->info('Package installed successfully..');
    }
}
