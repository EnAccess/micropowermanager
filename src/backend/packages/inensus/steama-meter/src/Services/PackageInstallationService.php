<?php

namespace Inensus\SteamaMeter\Services;

use Inensus\SteamaMeter\Helpers\ApiHelpers;

class PackageInstallationService {
    public function __construct(
        private SteamaSmsSettingService $smsSettingService,
        private SteamaSyncSettingService $syncSettingService,
        private SteamaSmsBodyService $smsBodyService,
        private SteamaSmsVariableDefaultValueService $defaultValueService,
        private SteamaSmsFeedbackWordService $steamaSmsFeedbackWordService,
    ) {}

    public function createDefaultSettingRecords() {
        $this->smsBodyService->createSmsBodies();
        $this->defaultValueService->createSmsVariableDefaultValues();
        $this->syncSettingService->createDefaultSettings();
        $this->smsSettingService->createDefaultSettings();
        $this->steamaSmsFeedbackWordService->createSmsFeedbackWord();
    }
}
