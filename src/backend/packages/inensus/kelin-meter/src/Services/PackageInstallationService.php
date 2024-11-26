<?php

namespace Inensus\KelinMeter\Services;

class PackageInstallationService {
    public function __construct(
        private KelinSyncSettingService $syncSettingService,
    ) {}

    public function createDefaultSettingRecords() {
        $this->syncSettingService->createDefaultSettings();
    }
}
