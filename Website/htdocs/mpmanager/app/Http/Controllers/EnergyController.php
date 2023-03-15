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
