<?php

namespace App\Plugins\KelinMeter\Services;

class PackageInstallationService {
    public function __construct(
        private KelinSyncSettingService $syncSettingService,
    ) {}

    public function createDefaultSettingRecords(): void {
        $this->syncSettingService->createDefaultSettings();
    }
}
