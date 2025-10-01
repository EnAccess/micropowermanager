<?php

namespace Inensus\SparkMeter\Services;

class PackageInstallationService {
    private SmSmsSettingService $smsSettingService;
    private SmSyncSettingService $syncSettingService;
    private SmSmsBodyService $smsBodyService;
    private SmSmsVariableDefaultValueService $defaultValueService;
    private SmSmsFeedbackWordService $smSmsFeedbackWordService;

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

    public function createDefaultSettingRecords(): void {
        $this->smsBodyService->createSmsBodies();
        $this->defaultValueService->createSmsVariableDefaultValues();
        $this->syncSettingService->createDefaultSettings();
        $this->smsSettingService->createDefaultSettings();
        $this->smSmsFeedbackWordService->createSmsFeedbackWord();
    }
}
