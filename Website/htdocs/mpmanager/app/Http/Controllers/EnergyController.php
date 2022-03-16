<?php

namespace App\Http\Controllers;

use App\Helpers\PowerConverter;
use App\Http\Requests\StoreEnergyRequest;
use App\Http\Resources\ApiResource;
use App\Models\Energy;
use Carbon\Carbon;
use Illuminate\Http\Request;

class EnergyController extends Controller
{
    /**
     * @var Energy
     */
    private $energy;

    public function __construct(Energy $energy)
    {
        $this->energy = $energy;
    }

    public function store(StoreEnergyRequest $request): ApiResource
    {
        $meters = $request->get('meters');

        foreach ($meters as $meter) {
            $lastEnergyInput = $this->energy::query()->where('meter_id', $meter['id'])
                ->where('active', 1)
                ->latest()
                ->first();

            $totalEnergy = 0;
            $totalAbsorbedEnergy = 0;
            $totalAbsorbedEnergyUnit = '';
            foreach ($meter['values'] as $value) {
                if ($value['name'] === 'Total yield') {
                    //get rid of thousand separator
                    $totalEnergy = str_replace(array('.', ','), array('', '.'), $value['value']);
                    break;
                }
                if ($value['name'] === 'Absorbed energy') {
                    $totalAbsorbedEnergy = str_replace(array('.', ','), array('', '.'), $value['value']);
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
                PowerConverter::convert($totalAbsorbedEnergy, $totalAbsorbedEnergyUnit, 'kW') -
                PowerConverter::convert($lastTotalAbsorbed, $lastTotalAbsorbedUnit, 'kW');


            $this->energy->newQuery()->create(
                [
                'meter_id' => $meter["id"],
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
                'absorbed_energy_since_last_unit' => 'kWh',

                ]
            );
        }
        return new ApiResource(['result' => 'success']);
    }


    public function show(Request $request, $miniGridId): ApiResource
    {
        $energyReadings = $this->energy->newQuery()
            ->where('mini_grid_id', $miniGridId);

        if ($startDate = $request->input('start_date')) {
            $energyReadings->where(
                'read_out',
                '>=',
                Carbon::createFromTimestamp($startDate)->format('Y-m-d H:i:s')
            );
        }
        if ($endDate = $request->input('end_date')) {
            $energyReadings->where(
                'read_out',
                '<=',
                Carbon::createFromTimestamp($endDate)->format('Y-m-d H:i:s')
            );
        }
        return new ApiResource($energyReadings->get());
    }
}
