<?php

namespace App\Plugins\GomeLongMeter\Observers;

use App\Models\MpmPlugin;
use App\Models\Tariff;
use App\Plugins\GomeLongMeter\Services\GomeLongCredentialService;
use App\Plugins\GomeLongMeter\Services\GomeLongTariffService;
use App\Traits\ScheduledPluginCommand;

class MeterTariffObserver {
    use ScheduledPluginCommand;

    public function __construct(
        private GomeLongTariffService $gomeLongTariffService,
        private GomeLongCredentialService $credentialService,
    ) {}

    public function created(Tariff $tariff): void {
        if (!$this->checkForPluginStatusIsActive(MpmPlugin::GOME_LONG_METERS)) {
            return;
        }
        $credential = $this->credentialService->getCredentials();
        if ($credential && $credential->getUserId() !== null && $credential->getUserPassword() !== null) {
            $this->gomeLongTariffService->createGomeLongTariff($tariff);
        }
    }

    public function updated(Tariff $tariff): void {
        if (!$this->checkForPluginStatusIsActive(MpmPlugin::GOME_LONG_METERS)) {
            return;
        }
        $credential = $this->credentialService->getCredentials();

        if ($credential && $credential->getUserId() !== null && $credential->getUserPassword() !== null) {
            $this->gomeLongTariffService->updateGomeLongTariff($tariff);
        }
    }

    public function deleted(Tariff $tariff): void {
        if (!$this->checkForPluginStatusIsActive(MpmPlugin::GOME_LONG_METERS)) {
            return;
        }
        $credential = $this->credentialService->getCredentials();

        if ($credential && $credential->getUserId() !== null && $credential->getUserPassword() !== null) {
            $this->gomeLongTariffService->deleteGomeLongTariff($tariff);
        }
    }
}
