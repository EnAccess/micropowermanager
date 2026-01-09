<?php

namespace Inensus\SparkMeter\Services;

class PackageInstallationService {
    public function __construct(private SmSmsSettingService $smsSettingService, private SmSyncSettingService $syncSettingService, private SmSmsBodyService $smsBodyService, private SmSmsVariableDefaultValueService $defaultValueService, private SmSmsFeedbackWordService $smSmsFeedbackWordService) {}

    public function createDefaultSettingRecords(): void {
        $this->smsBodyService->createSmsBodies();
        $this->defaultValueService->createSmsVariableDefaultValues();
        $this->syncSettingService->createDefaultSettings();
        $this->smsSettingService->createDefaultSettings();
        $this->smSmsFeedbackWordService->createSmsFeedbackWord();
    }
}
