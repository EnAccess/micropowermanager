<?php


namespace Inensus\SteamaMeter\Services;


use Inensus\SteamaMeter\Helpers\ApiHelpers;

class PackageInstallationService
{
    private $menuItemService;
    private $agentService;
    private $credentialService;
    private $paymentPlanService;
    private $tariffService;
    private $userTypeService;
    private $apiHelpers;
    private $siteService;
    private $smsSettingService;
    private $syncSettingService;
    private $smsBodyService;
    private $defaultValueService;
    private $steamaSmsFeedbackWordService;

    public function __construct(
        MenuItemService $menuItemService,
        SteamaAgentService $agentService,
        SteamaCredentialService $credentialService,
        SteamaSiteLevelPaymentPlanTypeService $paymentPlanService,
        SteamaTariffService $tariffService,
        SteamaUserTypeService $userTypeService,
        ApiHelpers $apiHelpers,
        SteamaSiteService $siteService,
        SteamaSmsSettingService $smsSettingService,
        SteamaSyncSettingService $syncSettingService,
        SteamaSmsBodyService $smsBodyService,
        SteamaSmsVariableDefaultValueService $defaultValueService,
        SteamaSmsFeedbackWordService $steamaSmsFeedbackWordService
    ) {

        $this->apiHelpers = $apiHelpers;
        $this->menuItemService = $menuItemService;
        $this->agentService = $agentService;
        $this->credentialService = $credentialService;
        $this->paymentPlanService = $paymentPlanService;
        $this->tariffService = $tariffService;
        $this->userTypeService = $userTypeService;
        $this->siteService = $siteService;
        $this->smsSettingService = $smsSettingService;
        $this->syncSettingService = $syncSettingService;
        $this->smsBodyService = $smsBodyService;
        $this->defaultValueService = $defaultValueService;
        $this->steamaSmsFeedbackWordService = $steamaSmsFeedbackWordService;
    }

    public function createDefaultSettingRecords()
    {
        $this->smsBodyService->createSmsBodies();
        $this->defaultValueService->createSmsVariableDefaultValues();
        $this->syncSettingService->createDefaultSettings();
        $this->smsSettingService->createDefaultSettings();
        $this->steamaSmsFeedbackWordService->createSmsFeedbackWord();
    }
}