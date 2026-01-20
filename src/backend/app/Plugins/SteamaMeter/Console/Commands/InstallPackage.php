<?php

namespace App\Plugins\SteamaMeter\Console\Commands;

use App\Models\Cluster;
use App\Plugins\SteamaMeter\Helpers\ApiHelpers;
use App\Plugins\SteamaMeter\Services\PackageInstallationService;
use App\Plugins\SteamaMeter\Services\SteamaAgentService;
use App\Plugins\SteamaMeter\Services\SteamaCredentialService;
use App\Plugins\SteamaMeter\Services\SteamaSiteLevelPaymentPlanTypeService;
use App\Plugins\SteamaMeter\Services\SteamaSiteService;
use App\Plugins\SteamaMeter\Services\SteamaTariffService;
use App\Plugins\SteamaMeter\Services\SteamaUserTypeService;
use Illuminate\Console\Command;

class InstallPackage extends Command {
    protected $signature = 'steama-meter:install';
    protected $description = 'Install Steamaco Meter Package';

    public function __construct(
        private SteamaAgentService $agentService,
        private SteamaCredentialService $credentialService,
        private SteamaSiteLevelPaymentPlanTypeService $paymentPlanService,
        private SteamaTariffService $tariffService,
        private SteamaUserTypeService $userTypeService,
        private ApiHelpers $apiHelpers,
        private SteamaSiteService $siteService,
        private PackageInstallationService $packageInstallationService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $this->info('Installing Steamaco Meter Integration Package\n');

        $this->packageInstallationService->createDefaultSettingRecords();
        $this->apiHelpers->registerSparkMeterManufacturer();
        $this->credentialService->createCredentials();
        $tariff = $this->tariffService->createTariff();
        $this->userTypeService->createUserTypes($tariff);
        $this->paymentPlanService->createPaymentPlans();
        $this->agentService->createSteamaAgentCommission();
        if (!$this->siteService->checkLocationAvailability() instanceof Cluster) {
            $this->warn('------------------------------');
            $this->warn('Steamaco Meter package needs least one registered Cluster.');
            $this->warn('If you have no Cluster, please navigate to #Locations# section and register your locations.');
        }
    }
}
