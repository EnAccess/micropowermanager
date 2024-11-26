<?php

namespace Inensus\GomeLongMeter\Observers;

use App\Models\Meter\MeterTariff;
// use App\Traits\ScheduledPluginCommand;
use App\Models\MpmPlugin;
use App\Traits\ScheduledPluginCommand;
use Inensus\GomeLongMeter\Services\GomeLongCredentialService;
use Inensus\GomeLongMeter\Services\GomeLongTariffService;

class MeterTariffObserver {
    use ScheduledPluginCommand;

    public function __construct(
        private GomeLongTariffService $gomeLongTariffService,
        private GomeLongCredentialService $credentialService,
    ) {}

    public function created(MeterTariff $tariff) {
        if (!$this->checkForPluginStatusIsActive(MpmPlugin::GOME_LONG_METERS)) {
            return;
        }
        $credential = $this->credentialService->getCredentials();
        if ($credential && $credential->getUserId() !== null && $credential->getUserPassword() !== null) {
            $this->gomeLongTariffService->createGomeLongTariff($tariff);
        }
    }

    public function updated(MeterTariff $tariff) {
        if (!$this->checkForPluginStatusIsActive(MpmPlugin::GOME_LONG_METERS)) {
            return;
        }
        $credential = $this->credentialService->getCredentials();

        if ($credential && $credential->getUserId() !== null && $credential->getUserPassword() !== null) {
            $this->gomeLongTariffService->updateGomeLongTariff($tariff);
        }
    }

    public function deleted(MeterTariff $tariff) {
        if (!$this->checkForPluginStatusIsActive(MpmPlugin::GOME_LONG_METERS)) {
            return;
        }
        $credential = $this->credentialService->getCredentials();

        if ($credential && $credential->getUserId() !== null && $credential->getUserPassword() !== null) {
            $this->gomeLongTariffService->deleteGomeLongTariff($tariff);
        }
    }
}
