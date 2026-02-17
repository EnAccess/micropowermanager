<?php

namespace App\Http\Controllers;

use App\Services\TariffService;
use App\Services\TimeOfUsageService;

class TimeOfUsageController extends Controller {
    public function __construct(
        private TimeOfUsageService $timeOfUsageService,
        private TariffService $tariffService,
    ) {}

    public function destroy(int $timeOfUsageId): ?bool {
        $timeOfUsage = $this->timeOfUsageService->getById($timeOfUsageId);
        $result = $this->timeOfUsageService->delete($timeOfUsage);

        if ($result) {
            $tariff = $this->tariffService->getById($timeOfUsage->tariff_id);
            $tariff->refresh();
        }

        return $result;
    }
}
