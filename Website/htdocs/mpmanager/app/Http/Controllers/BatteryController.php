<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBatteryStateRequest;
use App\Http\Resources\ApiResource;
use App\Models\Battery;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * @group   Battery
 * Class BatteryController
 * @package App\Http\Controllers
 */
class BatteryController extends Controller
{
    /**
     *
     * @var Battery
     */
    private $battery;

    public function __construct(Battery $battery)
    {
        $this->battery = $battery;
    }


    public function show(Request $request, $miniGridId): ApiResource
    {
        $batteryReadings = $this->battery->newQuery()
            ->where('mini_grid_id', $miniGridId);

        if ($startDate = $request->input('start_date')) {
            $batteryReadings->where(
                'read_out',
                '>=',
                Carbon::createFromTimestamp($startDate)->format('Y-m-d H:i:s')
            );
        }
        if ($endDate = $request->input('end_date')) {
            $batteryReadings->where(
                'read_out',
                '<=',
                Carbon::createFromTimestamp($endDate)->format('Y-m-d H:i:s')
            );
        }
        return new ApiResource($batteryReadings->get());
    }


    /**
     * Battery details for Mini-Grid
     *
     * @urlParam miniGridId int required
     * @urlParam limit int Default 50
     *
     * @param  Request $request
     * @param  $id
     * @return ApiResource
     */
    public function showByMiniGrid(Request $request, $miniGridId): ApiResource
    {
        $limit = $request->input('limit');

        $batteryReadings = $this->battery->newQuery()
            ->where('mini_grid_id', $miniGridId);

        if ($startDate = $request->input('start_date')) {
            $batteryReadings->where(
                'read_out',
                '>=',
                Carbon::createFromTimestamp($startDate)->format('Y-m-d H:i:s')
            );
        }
        if ($endDate = $request->input('end_date')) {
            $batteryReadings->where(
                'read_out',
                '<=',
                Carbon::createFromTimestamp($endDate)->format('Y-m-d H:i:s')
            );
        }
        if ($limit) {
            $batteryReadings->take($limit);
        }
        $batteryReadings->orderBy('read_out');
        $batteryReadings->latest();
        return new ApiResource($batteryReadings->get()->reverse()->values());
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

        $battery = $this->battery
            ->newQuery()
            ->create(
                [
                'mini_grid_id' => $request->input('mini_grid_id'),
                'node_id' => $request->input('node_id'),
                'device_id' => $request->input('device_id'),
                'battery_count' => $stateOfChargeData['count'],
                'soc_average' => $stateOfChargeData['average'],
                'soc_unit' => $stateOfChargeData['unit'],
                'soc_min' => $stateOfChargeData['min'] ?? 0,
                'soc_max' => $stateOfChargeData['max'] ?? 0,

                'soh_average' => 100 - (double)$stateOfHealthData['average'],
                'soh_unit' => $stateOfHealthData['unit'],
                'soh_min' => 100 - (double)($stateOfHealthData['min'] ?? 0),
                'soh_max' => 100 - (double)($stateOfHealthData['max'] ?? 0),

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
                ]
            );


        return new ApiResource($battery);
    }
}
