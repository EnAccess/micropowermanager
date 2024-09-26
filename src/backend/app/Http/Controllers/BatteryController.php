<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\Battery;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * @group   Battery
 * Class BatteryController
 */
class BatteryController extends Controller
{
    /**
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
     * Battery details for Mini-Grid.
     *
     * @urlParam miniGridId int required
     * @urlParam limit int Default 50
     *
     * @param Request $request
     * @param         $id
     *
     * @return ApiResource
     */
    public function showByMiniGrid(Request $request, $miniGridId): ApiResource
    {
        $limit = $request->input('per_page');

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
}
