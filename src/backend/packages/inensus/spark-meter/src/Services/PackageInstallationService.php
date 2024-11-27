<?php

namespace Inensus\SparkMeter\Services;

class PackageInstallationService {
    private $smsSettingService;
    private $syncSettingService;
    private $smsBodyService;
    private $defaultValueService;
    private $smSmsFeedbackWordService;

    public function __construct(
        SmSmsSettingService $smsSettingService,
        SmSyncSettingService $syncSettingService,
        SmSmsBodyService $smsBodyService,
        SmSmsVariableDefaultValueService $defaultValueService,
        SmSmsFeedbackWordService $smSmsFeedbackWordService,
    ) {
        $this->smsSettingService = $smsSettingService;
        $this->syncSettingService = $syncSettingService;
        $this->smsBodyService = $smsBodyService;
        $this->defaultValueService = $defaultValueService;
        $this->smSmsFeedbackWordService = $smSmsFeedbackWordService;
    }

    public function createDefaultSettingRecords() {
        $this->smsBodyService->createSmsBodies();
        $this->defaultValueService->createSmsVariableDefaultValues();
        $this->syncSettingService->createDefaultSettings();
        $this->smsSettingService->createDefaultSettings();
        $this->smSmsFeedbackWordService->createSmsFeedbackWord();
    }
}
