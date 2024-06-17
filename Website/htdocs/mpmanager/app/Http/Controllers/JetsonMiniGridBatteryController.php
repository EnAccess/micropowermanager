<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBatteryStateRequest;
use App\Http\Resources\ApiResource;
use App\Services\MiniGridBatteryService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class JetsonMiniGridBatteryController extends Controller
{
    public function __construct(private MiniGridBatteryService $miniGridBatteryService)
    {
    }

    public function show($miniGridId, Request $request): ApiResource
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        return ApiResource::make($this->miniGridBatteryService->getForJetsonById($miniGridId, $startDate, $endDate));
    }

    /**
     * Store battery status
     *
     * @urlParam miniGridId integer required
     * @param    StoreBatteryStateRequest $request
     * @return   ApiResource
     */
    public function store(StoreBatteryStateRequest $request): ApiResource
    {
        $batteryData = $request->input('batteries');
        $stateOfChargeData = $batteryData['state_of_charge'];
        $stateOfHealthData = $batteryData['state_of_health'];
        $total = $batteryData['battery_discharge'];
        $charge = $batteryData['battery_charge'];
        $temperature = $batteryData['temperature'];

        // temperature values have ':' before the number when the value is < 0
        $temperature['min'] = str_replace(':', '-', $temperature['min']);
        $temperature['max'] = str_replace(':', '-', $temperature['max']);
        $temperature['average'] = str_replace(':', '-', $temperature['average']);

        $batteryData =   [
            'mini_grid_id' => $request->input('mini_grid_id'),
            'node_id' => $request->input('node_id'),
            'device_id' => $request->input('device_id'),
            'battery_count' => $stateOfChargeData['count'],
            'soc_average' => $stateOfChargeData['average'],
            'soc_unit' => $stateOfChargeData['unit'],
            'soc_min' => $stateOfChargeData['min'] ?? 0,
            'soc_max' => $stateOfChargeData['max'] ?? 0,

            'soh_average' => 100 - (float)$stateOfHealthData['average'],
            'soh_unit' => $stateOfHealthData['unit'],
            'soh_min' => 100 - (float)($stateOfHealthData['min'] ?? 0),
            'soh_max' => 100 - (float)($stateOfHealthData['max'] ?? 0),

            'd_total' => str_replace(',', '.', $total['discharge']),
            'd_total_unit' => $total['unit'],
            'd_newly_energy' => 0,
            'd_newly_energy_unit' => 'Wh',

            'c_total' => str_replace(',', '.', $charge['charge']),
            'c_total_unit' => $charge['unit'],
            'c_newly_energy' => 0,
            'c_newly_energy_unit' => 'Wh',
            'temperature_min' => $temperature['min'],
            'temperature_max' => $temperature['max'],
            'temperature_average' => $temperature['average'],
            'temperature_unit' => $temperature['unit'],
            'read_out' => date('Y-m-d H:i:s', strtotime($batteryData['time_stamp'])),
        ];
        return  ApiResource::make($this->miniGridBatteryService->create($batteryData));
    }
}
