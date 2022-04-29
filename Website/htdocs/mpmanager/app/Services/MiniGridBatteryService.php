<?php

namespace App\Services;

use App\Models\Battery;
use App\Models\MiniGrid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class MiniGridBatteryService extends BaseService
{
    public function __construct(private Battery $battery, private MiniGrid $miniGrid)
    {
        parent::__construct([$battery, $miniGrid]);
    }

    public function getById($miniGridId,$startDate,$endDate,$limit): Collection|array
    {
        $batteryReadings = $this->battery->newQuery()
            ->where('mini_grid_id', $miniGridId);
        if ($startDate) {
            $batteryReadings->where(
                'read_out',
                '>=',
                Carbon::createFromTimestamp($startDate)->format('Y-m-d H:i:s')
            );
        }

        if ($endDate) {
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

        return $batteryReadings->get()->reverse()->values();
    }

    public function getForJetsonById($miniGridId,$startDate,$endDate)
    {
        $batteryReadings = $this->battery->newQuery()
            ->where('mini_grid_id', $miniGridId);
        if ($startDate) {
            $batteryReadings->where(
                'read_out',
                '>=',
                Carbon::createFromTimestamp($startDate)->format('Y-m-d H:i:s')
            );
        }

        if ($endDate) {
            $batteryReadings->where(
                'read_out',
                '<=',
                Carbon::createFromTimestamp($endDate)->format('Y-m-d H:i:s')
            );
        }

        return $batteryReadings->get();
    }
}