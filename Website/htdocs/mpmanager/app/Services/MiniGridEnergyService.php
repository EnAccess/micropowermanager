<?php

namespace App\Services;

use App\Http\Resources\ApiResource;
use App\Models\Energy;
use App\Models\MiniGrid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class MiniGridEnergyService
{
    public function __construct(private MiniGrid $miniGrid, private Energy $energy)
    {
    }

    public function getById($miniGridId, $startDate, $endDate): Collection|array
    {
        $energyReadings = $this->energy->newQuery()
            ->where('mini_grid_id', $miniGridId);

        if ($startDate) {
            $energyReadings->where(
                'read_out',
                '>=',
                Carbon::createFromTimestamp($startDate)->format('Y-m-d H:i:s')
            );
        }

        if ($endDate) {
            $energyReadings->where(
                'read_out',
                '<=',
                Carbon::createFromTimestamp($endDate)->format('Y-m-d H:i:s')
            );
        }

        return  $energyReadings->get();
    }
}
