<?php

namespace App\Plugins\SparkMeter\Http\Controllers;

use App\Plugins\SparkMeter\Http\Requests\SmTariffRequest;
use App\Plugins\SparkMeter\Http\Resources\SparkResource;
use App\Plugins\SparkMeter\Services\TariffService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

#[Group('Plugins / Spark Meter')]
class SmTariffController extends Controller implements IBaseController {
    public function __construct(
        private TariffService $tariffService,
    ) {}

    public function index(Request $request): SparkResource {
        return new SparkResource($this->tariffService->getSmTariffs($request));
    }

    public function getInfo(string $tariffId): SparkResource {
        return new SparkResource($this->tariffService->getSparkTariffInfo($tariffId));
    }

    public function updateInfo(SmTariffRequest $request): SparkResource {
        // SparkMeter's PUT /tariff/:id is a full overwrite that erases any field
        // not present in the request, so fields the caller didn't specify must
        // fall back to the tariff's current remote value rather than null --
        // otherwise an edit that only touches e.g. the price would silently wipe
        // out TOU/plan/daily-limit settings on SparkMeter's side.
        $currentTariff = $this->tariffService->getSparkTariffInfo($request->input('id'));

        $tariffData = [
            'id' => $request->input('id'),
            'name' => $request->input('name') ?? $currentTariff['name'],
            'flat_price' => $request->input('flatPrice') ?? $currentTariff['flat_price'],
            'flat_load_limit' => $request->input('flatLoadLimit') ?? $currentTariff['flat_load_limit'],
            'daily_energy_limit_enabled' => $request->input('dailyEnergyLimitEnabled') ?? $currentTariff['daily_energy_limit_enabled'],
            'daily_energy_limit_value' => $request->input('dailyEnergyLimitValue') ?? $currentTariff['daily_energy_limit_value'],
            'daily_energy_limit_reset_hour' => $request->input('dailyEnergyLimitResetHour') ?? $currentTariff['daily_energy_limit_reset_hour'],
            'tou_enabled' => $request->input('touEnabled') ?? $currentTariff['tou_enabled'],
            'tous' => $request->input('tous') ?? $currentTariff['tous'],
            'plan_enabled' => $request->input('planEnabled') ?? $currentTariff['plan_enabled'],
            'plan_duration' => $request->input('planDuration') ?? $currentTariff['plan_duration'],
            'plan_price' => $request->input('planPrice') ?? $currentTariff['plan_price'],
            'planFixedFee' => $request->input('planFixedFee') ?? ($currentTariff['plan_fixed_fee'] ?? 0),
        ];

        return new SparkResource($this->tariffService->updateSparkTariffInfo($tariffData));
    }

    public function sync(): SparkResource {
        return new SparkResource($this->tariffService->sync());
    }

    public function checkSync(): SparkResource {
        return new SparkResource($this->tariffService->syncCheck());
    }

    public function count(): int {
        return $this->tariffService->getSmTariffsCount();
    }
}
