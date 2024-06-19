<?php

namespace App\Http\Controllers;

use App\Helpers\PowerConverter;
use App\Http\Resources\ApiResource;
use App\Models\PV;
use Carbon\Carbon;
use Illuminate\Http\Request;

/**
 * @group   PV
 * Class PVController
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
     * List for Mini-Grid.
     *
     * @urlParam limit int Default = 50
     *
     * @param         $miniGridId
     * @param Request $request
     *
     * @return ApiResource
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
        $val = (float) str_replace('.', '', $val);
        $val = (float) str_replace(',', '.', $val);

        return $val;
    }
}
