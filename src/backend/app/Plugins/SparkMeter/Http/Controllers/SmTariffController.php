<?php

namespace App\Plugins\SparkMeter\Http\Controllers;

use App\Plugins\SparkMeter\Http\Requests\SmTariffRequest;
use App\Plugins\SparkMeter\Http\Resources\SparkResource;
use App\Plugins\SparkMeter\Services\TariffService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

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
        $tariffData = [
            'id' => $request->input('id'),
            'name' => $request->input('name'),
            'flat_price' => $request->input('flatPrice'),
            'flat_load_limit' => $request->input('flatLoadLimit'),
            'daily_energy_limit_enabled' => $request->input('dailyEnergyLimitEnabled'),
            'daily_energy_limit_value' => $request->input('dailyEnergyLimitValue'),
            'daily_energy_limit_reset_hour' => $request->input('dailyEnergyLimitResetHour'),
            'tou_enabled' => $request->input('touEnabled'),
            'tous' => $request->input('tous'),
            'plan_enabled' => $request->input('planEnabled'),
            'plan_duration' => $request->input('planDuration'),
            'plan_price' => $request->input('planPrice'),
            'planFixedFee' => $request->input('planFixedFee'),
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
