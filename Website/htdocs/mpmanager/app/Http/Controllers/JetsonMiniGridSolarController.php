<?php

namespace App\Http\Controllers;

use App\Http\Requests\SolarCreateRequest;
use App\Http\Resources\ApiResource;
use App\Models\Solar;
use App\Services\MiniGridSolarService;
use Illuminate\Http\Request;

class JetsonMiniGridSolarController extends Controller
{
    public function __construct(private MiniGridSolarService $miniGridSolarService)
    {
    }

    public function show($miniGridId, Request $request): ApiResource
    {
        $limit = $request->get('limit');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $weatherData = $request->input('weather');
        $startDate = request()->input('start_date');
        $endDate = request()->input('end_date');
        $limit = request()->input('per_page');
        $withWeather = request()->input('weather');

        $query = Solar::query()->where('mini_grid_id', '=', $miniGridId);

        if ($startDate) {
            $query->where('created_at', '>=', $startDate);
        }

        if ($endDate) {
            $query->where('created_at', '<=', $endDate);
        }

        if ($withWeather) {
            $query->with('weatherData');
        }

        if ($limit) {
            $query->take($limit);
        }

        return  ApiResource::make($query->get());
    }

    public function store(SolarCreateRequest $request): ApiResource
    {
        $solarData = $request->input('solar_reading');
        $frequencyStr = $request->input('frequency');
        $pvPowerStr = $request->input('pv_power');
        $miniGridId = $request->input('mini_grid_id');
        $deviceId = $request->input('device_id');
        $nodeId = $request->input('node_id');
        $timestamp = $request->input('time_stamp');
        $storageFolder = $request->input('storage_folder');

        if ($frequencyStr !== "0" && $frequencyStr !== 0) {
            $frequency = (int)str_replace('\n', '', $frequencyStr);
        } else {
            $frequency = null;
        }

        if ($pvPowerStr !== "0" && $pvPowerStr !== 0) {
            $pvPower = (int)str_replace('\n', '', $pvPowerStr);
        } else {
            $pvPower = null;
        }

        $solarData = [
            'mini_grid_id' => $miniGridId,
            'node_id' => $nodeId,
            'device_id' => $deviceId,
            'time_stamp' => $timestamp,
            'starting_time' => $solarData['starting_time'] ?? 0,
            'ending_time' => $solarData['ending_time'] ?? 0,
            'min' => (int)($solarData['min'] ?? 0),
            'max' => (int)($solarData['max'] ?? 0),
            'average' => (int)$solarData['average'],
            'duration' => $solarData['duration'] ?? 0,
            'readings' => $solarData['readings'],
            'frequency' => $frequency,
            'pv_power' => $pvPower,
            'storage_folder' => $storageFolder
        ];

        return ApiResource::make($this->miniGridSolarService->create($solarData));
    }
}
