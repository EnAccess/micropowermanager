<?php
namespace Inensus\AirtelPaymentProvider\Console\Commands;

use Illuminate\Console\Command;

class InstallPackage extends Command
{
    protected $signature = 'airtel-payment-provider:install';
    protected $description = 'Install AirtelPaymentProvider Package';

    public function __construct(
)
    {
        parent::__construct();
    }

    public function handle(): void
    {
        $this->info('Installing AirtelPaymentProvider Integration Package\n');
        $this->info('Package installed successfully..');
    }

}