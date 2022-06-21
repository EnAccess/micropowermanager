<?php


namespace Inensus\KelinMeter\Services;


class PackageInstallationService
{
    private $menuItemService;
    private $syncSettingService;

    public function __construct(
        MenuItemService $menuItemService,
        KelinSyncSettingService $syncSettingService
    ) {

        $this->menuItemService = $menuItemService;
        $this->syncSettingService = $syncSettingService;
    }
    public function createDefaultSettingRecords()
    {
        $this->syncSettingService->createDefaultSettings();
    }
}
