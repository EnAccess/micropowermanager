<?php

namespace App\Services;

use App\Models\MiniGrid;
use App\Models\Solar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class MiniGridSolarService
{
    public function __construct(
        private MiniGrid $miniGrid,
        private Solar $solar
    ) {
    }

    /**
     * @return Builder|Model|null
     */
    public function getById(int $miniGridId): Model|Builder|null
    {
        return $this->solar->newQuery()->where('mini_grid_id', $miniGridId)->first();
    }

    public function getForJetsonById($miniGridId, $startDate, $endDate, $limit, $weatherData): Collection|array
    {

        $solarReadings = $this->solar->newQuery()
            ->where('mini_grid_id', $miniGridId);

        if ($startDate) {
            $solarReadings->where(
                'time_stamp',
                '>=',
                Carbon::createFromTimestamp($startDate)->format('Y-m-d H:i:s')
            );
        }

        if ($endDate) {
            $solarReadings->where(
                'time_stamp',
                '<=',
                Carbon::createFromTimestamp($endDate)->format('Y-m-d H:i:s')
            );
        }

        if ($limit) {
            $solarReadings->take($limit);
        }

        if ($weatherData) {
            $solarReadings->with('weatherData');
        }

        return $solarReadings->get();
    }
    public function create($solarData)
    {

        $solar = $this->solar->newQuery()->create($solarData);
        $solar->fraction =
            round($this->findSlope($solarData['mini_grid_id'], $solarData['node_id'], $solarData['device_id']), 5);
        $solar->save();
        return $solar;
    }
    private function findSlope($miniGridId, $nodeId, $deviceId)
    {

        $query = $this->solar->newQuery()
            ->where('mini_grid_id', $miniGridId)
            ->where('node_id', $nodeId)
            ->where('device_id', $deviceId)
            ->whereNotNull('pv_power')
            ->whereNotNull('frequency')
            ->where('average', '>', 0)
            ->where('pv_power', '>', 0)
            ->where('frequency', '>', 0)
            ->where('frequency', '<=', 50000)->get();

        $pvPowers = $query->pluck('pv_power')->toArray();
        $solarReadings = $query->pluck('average')->toArray();

        $x = $solarReadings;
        $y = $pvPowers;

        if (count($x) && count($y)) {
            $n = count($x);
            $sum_x = 0;
            $sum_y = 0;
            $sum_xy = 0;
            $sum_xx = 0;
            $sum_yy = 0;

            for ($i = 0; $i < $n; $i++) {
                $sum_x += $x[$i];
                $sum_y += $y[$i];
                $sum_xy += ($x[$i] * $y[$i]);
                $sum_xx += ($x[$i] * $x[$i]);
                $sum_yy += ($y[$i] * $y[$i]);
            }
            try {
                return ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_xx - $sum_x * $sum_x);
            } catch (\Exception $e) {
                Log::error('Error in finding slope: ' . $e->getMessage());
                return 0;
            }
        }
        return 0;
    }
}
