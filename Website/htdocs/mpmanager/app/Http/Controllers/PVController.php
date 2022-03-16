<?php

namespace App\Http\Controllers;

use App\Helpers\PowerConverter;
use App\Http\Requests\PVRequest;
use App\Http\Resources\ApiResource;
use App\Models\PV;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * @group   PV
 * Class PVController
 * @package App\Http\Controllers
 */
class PVController extends Controller
{
    /**
     * @var
     */
    private $pv;


    public function __construct(PV $pv)
    {
        $this->pv = $pv;
    }


    public function showReadings(Request $request, $miniGridId): ApiResource
    {
        $pvReadings = $this->pv->newQuery()
            ->where('mini_grid_id', $miniGridId);

        if ($startDate = $request->input('start_date')) {
            $pvReadings->where(
                'reading_date',
                '>=',
                Carbon::createFromTimestamp($startDate)->format('Y-m-d H:i:s')
            );
        }
        if ($endDate = $request->input('end_date')) {
            $pvReadings->where(
                'reading_date',
                '<=',
                Carbon::createFromTimestamp($endDate)->format('Y-m-d H:i:s')
            );
        }
        return new ApiResource($pvReadings->get());
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
    public function store(PVRequest $request, Response $response): ?Response
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


        $this->pv
            ->newQuery()
            ->create(
                [
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
                ]
            );
    }

    /**
     * List for Mini-Grid
     *
     * @urlParam limit int Default = 50
     * @param    $miniGridId
     * @param    Request $request
     * @return   ApiResource
     */
    public function show($miniGridId, Request $request): ApiResource
    {
        $limit = $request->get('limit') ?? 96;
        $miniGridPVs = $this->pv->newQuery()
            ->where('mini_grid_id', $miniGridId)
            ->latest()
            ->take($limit)
            ->get()
            ->reverse()
            ->values();

        foreach ($miniGridPVs as $index => $miniGridPV) {
            $miniGridPVs[$index]['daily'] = PowerConverter::convert($miniGridPV->daily, $miniGridPV->daily_unit, 'kWh');
            $miniGridPVs[$index]['daily_unit'] = 'kWh';

            $miniGridPVs[$index]['new_generated_energy'] = PowerConverter::convert(
                $miniGridPV->new_generated_energy,
                $miniGridPV->new_generated_energy_unit,
                'kWh'
            );

            $miniGridPVs[$index]['new_generated_energy_unit'] = 'kWh';
        }
        return new ApiResource($miniGridPVs);
    }


    private function formatEnergyData($val): float
    {
        $val = (double)(str_replace('.', '', $val));
        $val = (double)(str_replace(',', '.', $val));
        return $val;
    }
}
