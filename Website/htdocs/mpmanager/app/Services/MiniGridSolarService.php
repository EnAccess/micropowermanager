<?php

namespace App\Services;

use App\Models\MiniGrid;
use App\Models\Solar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class MiniGridSolarService extends BaseService
{
    public function __construct(
        private MiniGrid $miniGrid,
        private Solar $solar
    ) {
        parent::__construct([$solar, $miniGrid]);
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
}