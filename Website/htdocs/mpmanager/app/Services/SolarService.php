<?php

namespace App\Services;

use App\Models\Solar;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class SolarService implements ISolarService
{
    public function create(): Solar
    {
        $solarData = request()->input('solar_reading');

        $solarRecord = [
            'node_id' => request()->input('node_id'),
            'device_id' => request()->input('device_id'),
            'mini_grid_id' => request()->input('mini_grid_id'),
            'starting_time' => $solarData['starting_time'] ?? 0,
            'readings' => $solarData['readings'],
            'average' => (int) $solarData['average'],
            'min' => (int) ($solarData['min'] ?? 0),
            'max' => (int) ($solarData['max'] ?? 0),
            'duration' => $solarData['duration'] ?? 0,
            'ending_time' => $solarData['ending_time'] ?? 0,
            'time_stamp' => request()->input('time_stamp'),
        ];

        /** @var Solar $solar */
        $solar = Solar::query()->create($solarRecord);

        return $solar;
    }

    /**
     * @return Builder[]|Collection
     *
     * @psalm-return Collection|array<array-key, Builder>
     */
    public function list(): Collection
    {
        $solarReadings = $this->filter(Solar::query());

        return $solarReadings->get();
    }

    /**
     * @return Builder[]|Collection
     *
     * @psalm-return Collection|array<array-key, Builder>
     */
    public function lisByMiniGrid(int $miniGridId): Collection
    {
        $solarReadings = $this->filter(Solar::query());
        $solarReadings->where('mini_grid_id', $miniGridId);

        return $solarReadings->get();
    }

    /**
     * @return Builder|Model|null
     */
    public function showByMiniGrid(int $miniGridId): ?Solar
    {
        return Solar::query()->where('mini_grid_id', $miniGridId)->first();
    }

    private function filter(Builder $query): Builder
    {
        if ($startDate = request()->input('start_date')) {
            $query->where(
                'time_stamp',
                '>=',
                Carbon::createFromTimestamp($startDate)->format('Y-m-d H:i:s')
            );
        }
        if ($endDate = request()->input('end_date')) {
            $query->where(
                'time_stamp',
                '<=',
                Carbon::createFromTimestamp($endDate)->format('Y-m-d H:i:s')
            );
        }
        if ($limit = request()->input('limit')) {
            $query->take($limit);
        }
        if (request()->input('weather')) {
            $query->with('weatherData');
        }

        return $query;
    }
}
