<?php

namespace App\Http\Controllers;

use App\Helpers\PowerConverter;
use App\Http\Requests\StoreEnergyRequest;
use App\Http\Resources\ApiResource;
use App\Models\Energy;
use App\Services\MiniGridEnergyService;
use Illuminate\Http\Request;

class JetsonMiniGridEnergyController extends Controller
{
    public function __construct(private MiniGridEnergyService $miniGridEnergyService)
    {
    }

    public function show($miniGridId, Request $request): ApiResource
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $startDate = request()->input('start_date');
        $endDate = request()->input('end_date');
        $limit = request()->input('per_page');
        $withWeather = request()->input('weather');

        $query = Energy::query()
            ->where('mini_grid_id', $miniGridId);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        if ($limit) {
            $query->take($limit);
        }

        return new ApiResource($query->get());
    }

    public function store(StoreEnergyRequest $request): ApiResource
    {
        $meters = $request->get('meters');

        foreach ($meters as $meter) {
            $lastEnergyInput = Energy::query()->where('meter_id', $meter['id'])
                ->where('active', 1)
                ->latest()
                ->first();

            $totalEnergy = 0;
            $totalAbsorbedEnergy = 0;
            $totalAbsorbedEnergyUnit = '';
            foreach ($meter['values'] as $value) {
                if ($value['name'] === 'Total yield') {
                    // get rid of thousand separator
                    $totalEnergy = str_replace(['.', ','], ['', '.'], $value['value']);
                    break;
                }
                if ($value['name'] === 'Absorbed energy') {
                    $totalAbsorbedEnergy = str_replace(['.', ','], ['', '.'], $value['value']);
                    $totalAbsorbedEnergyUnit = $value['unit'];
                }
            }

            if ($lastEnergyInput !== null) {
                $lastTotalEnergy = $lastEnergyInput->total_energy;
                $lastTotalAbsorbed = $lastEnergyInput->total_absorbed;
                $lastTotalAbsorbedUnit = $lastEnergyInput->total_absorbed_unit;
                $lastEnergyInput->update(['active' => 0]);
            } else {
                $lastTotalEnergy = $totalEnergy;
                $lastTotalAbsorbed = $totalAbsorbedEnergy;
                $lastTotalAbsorbedUnit = $totalAbsorbedEnergyUnit;
            }

            $usedEnergySinceLastInput = $totalEnergy - $lastTotalEnergy;
            $absorbedEnergySinceLastInput =
                PowerConverter::convert($totalAbsorbedEnergy, $totalAbsorbedEnergyUnit, 'Wh') -
                PowerConverter::convert($lastTotalAbsorbed, $lastTotalAbsorbedUnit, 'Wh');
            Energy::query()->create(
                [
                    'meter_id' => $meter['id'],
                    'active' => 1,
                    'mini_grid_id' => $request->input('mini_grid_id'),
                    'node_id' => $request->input('node_id'),
                    'device_id' => $request->input('device_id'),
                    'total_energy' => $totalEnergy,
                    'read_out' => $request->input('read_out'),
                    'used_energy_since_last' => $usedEnergySinceLastInput,
                    'total_absorbed' => $totalAbsorbedEnergy,
                    'total_absorbed_unit' => $totalAbsorbedEnergyUnit,
                    'absorbed_energy_since_last' => $absorbedEnergySinceLastInput,
                    'absorbed_energy_since_last_unit' => 'Wh',
                ]
            );
        }

        return new ApiResource(['result' => 'success']);
    }
}
