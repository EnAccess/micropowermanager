<?php

namespace Inensus\SteamaMeter\Console\Commands;

use Illuminate\Console\Command;
use Inensus\SteamaMeter\Helpers\ApiHelpers;
use Inensus\SteamaMeter\Services\PackageInstallationService;
use Inensus\SteamaMeter\Services\SteamaAgentService;
use Inensus\SteamaMeter\Services\SteamaCredentialService;
use Inensus\SteamaMeter\Services\SteamaSiteLevelPaymentPlanTypeService;
use Inensus\SteamaMeter\Services\SteamaSiteService;
use Inensus\SteamaMeter\Services\SteamaSmsBodyService;
use Inensus\SteamaMeter\Services\SteamaSmsFeedbackWordService;
use Inensus\SteamaMeter\Services\SteamaSmsSettingService;
use Inensus\SteamaMeter\Services\SteamaSmsVariableDefaultValueService;
use Inensus\SteamaMeter\Services\SteamaSyncSettingService;
use Inensus\SteamaMeter\Services\SteamaTariffService;
use Inensus\SteamaMeter\Services\SteamaUserTypeService;

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
        if (!$this->siteService->checkLocationAvailability()) {
            $this->warn('------------------------------');
            $this->warn('Steamaco Meter package needs least one registered Cluster.');
            $this->warn('If you have no Cluster, please navigate to #Locations# section and register your locations.');
        }
    }
}
