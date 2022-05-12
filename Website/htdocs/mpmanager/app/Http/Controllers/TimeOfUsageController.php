<?php

namespace App\Http\Controllers;

use App\Models\Meter\MeterTariff;
use App\Models\TimeOfUsage;
use App\Services\MeterTariffService;
use App\Services\TimeOfUsageService;
use Illuminate\Http\Request;

class TimeOfUsageController extends Controller
{
    public function __construct(
        private TimeOfUsageService $timeOfUsageService,
        private MeterTariffService $meterTariffService
    ) {
    }

    public function destroy($timeOfUsageId): ?bool
    {
        $timeOfUsage = $this->timeOfUsageService->getById($timeOfUsageId);
        $result = $this->timeOfUsageService->delete($timeOfUsage);

        if ($result) {
            $meterTariff = $this->meterTariffService->getById($timeOfUsage->tariff_id);

            return $result;
        }

        return $result;
    }
}
