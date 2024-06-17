<?php

namespace App\Http\Controllers;

use App\Http\Requests\PVRequest;
use App\Http\Resources\ApiResource;
use App\Services\MiniGridPVService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class JetsonMiniGridPVController extends Controller
{
    public function __construct(private MiniGridPVService $miniGridPVService)
    {
    }

    public function show($miniGridId, Request $request): ApiResource
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        return ApiResource::make($this->miniGridPVService->getById($miniGridId, $startDate, $endDate));
    }


    /**
     * Create
     *
     * @param PVRequest $request
     * @param Response  $response
     *
     * @bodyParam mini_grid_id int required
     * @bodyParam node_id int required
     * @bodyParam device_id int required
     * @bodyParam pv array required
     *
     * @return Response|null
     */
    public function store(PVRequest $request, Response $response): Response | ApiResource
    {
        $pv = $request->input('pv');

        if (!array_key_exists('daily', $pv) || !array_key_exists('total', $pv)) {
            return $response->setStatusCode(422)->setContent(
                [
                    'data' => [
                        'message' => 'daily , total are required',
                        'status_code' => 422
                    ]
                ]
            );
        }

        $dailyGeneratedEnergy = $this->formatEnergyData($pv['daily']['energy']);
        $totalGeneratedEnergy = $this->formatEnergyData($pv['total']['energy']);
        $pvData =  [
            'mini_grid_id' => $request->input('mini_grid_id'),
            'node_id' => $request->input('node_id'),
            'device_id' => $request->input('device_id'),
            'reading_date' => Carbon::createFromFormat('d.m.Y H:i', $pv['time_stamp'])->toDateTimeString(),
            'daily' => $dailyGeneratedEnergy,
            'daily_unit' => $pv['daily']['unit'],
            'total' => $totalGeneratedEnergy,
            'total_unit' => $pv['total']['unit'],
            'new_generated_energy' => 0,
            'new_generated_energy_unit' => 'Wh'
        ];

        return ApiResource::make($this->miniGridPVService->create($pvData));
    }

    private function formatEnergyData($val): float
    {
        $val = (float)(str_replace('.', '', $val));
        $val = (float)(str_replace(',', '.', $val));
        return $val;
    }
}
