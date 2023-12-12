<?php

namespace App\Services;

use App\Models\ConnectionGroup;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterToken;
use App\Models\Revenue;
use App\Models\Transaction\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MeterRevenueService
{
    public function __construct(
        private MeterToken $meterToken,
        private Transaction $transaction
    ) {
    }

    public function getBySerialNumber(string $serialNumber)
    {
        $tokens = $this->meterToken->newQuery()->whereHas(
            'meter',
            function ($q) use ($serialNumber) {
                $q->where('serial_number', $serialNumber);
            }
        )->pluck('transaction_id');

        return $this->transaction->newQuery()->whereIn('id', $tokens)->sum('amount');
    }

    public function getConnectionGroupBasedRevenueForCluster(
        $clusterId,
        int $connectionGroupId,
        string $startDate,
        string $endDate
    ): array {
        return Transaction::query()
            ->selectRaw('SUM(transactions.amount) as total')
            ->selectSub(
                ConnectionGroup::query()
                    ->select('name')
                    ->where('id', $connectionGroupId)
                    ->limit(1),
                'connection'
            )
            ->whereIn('transactions.message', function ($query) use ($connectionGroupId, $clusterId) {
                $query->select('serial_number')
                    ->from('meters')
                    ->leftJoin('devices', 'devices.device_id', '=', 'meters.id')
                    ->leftJoin('addresses', 'addresses.owner_id', '=', 'devices.id')
                    ->where('devices.device_type', 'meter')
                    ->where('meters.connection_group_id', $connectionGroupId)
                    ->where('addresses.owner_type', 'device')
                    ->whereIn('addresses.city_id', function ($query) use ($clusterId) {
                        $query->select('id')
                            ->from('cities')
                            ->where('cluster_id', $clusterId);
                    });
            })
            ->whereHasMorph(
                'originalTransaction',
                '*',
                static function ($q) {
                    return $q->where('status', 1);
                }
            )
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->get()->toArray();
    }

    public function getConnectionTypeBasedRevenueInWeeklyPeriodForCities(
        string $cityIds,
        int $connectionId,
        string $startDate,
        string $endDate
    ): Collection {
        return Transaction::query()
            ->selectRaw('SUM(transactions.amount) as total, YEARWEEK(transactions.created_at, 3) as result_date')
            ->whereIn('transactions.message', function ($query) use ($connectionId, $cityIds) {
                $query->select('serial_number')
                    ->from('meters')
                    ->leftJoin('devices', 'devices.device_id', '=', 'meters.id')
                    ->leftJoin('addresses', 'addresses.owner_id', '=', 'devices.id')
                    ->where('addresses.owner_type', 'device')
                    ->where('devices.device_type', 'meter')
                    ->where('meters.connection_type_id', $connectionId)
                    ->whereIn(
                        'addresses.city_id',
                        explode(',', $cityIds)
                    );  // assuming $miniGridId is a comma-separated string
            })
            ->whereHasMorph(
                'originalTransaction',
                '*',
                static function ($q) {
                    return $q->where('status', 1);
                }
            )
            ->whereBetween(DB::raw('DATE(transactions.created_at)'), [$startDate, $endDate])
            ->groupBy(DB::raw('YEARWEEK(transactions.created_at, 3)'))
            ->get();
    }

    public function getConnectionGroupBasedRevenueForMiniGrid(
        int $miniGridId,
        int $connectionGroupId,
        string $startDate,
        string $endDate
    ): array {
        return Transaction::query()
            ->selectRaw('SUM(transactions.amount) as total')
            ->selectSub(
                ConnectionGroup::query()
                    ->select('name')
                    ->where('id', $connectionGroupId)
                    ->limit(1),
                'connection'
            )
            ->whereIn('transactions.message', function ($query) use ($connectionGroupId, $miniGridId) {
                $query->select('serial_number')
                    ->from('meters')
                    ->leftJoin('devices', 'devices.device_id', '=', 'meters.id')
                    ->leftJoin('addresses', 'addresses.owner_id', '=', 'devices.id')
                    ->where('devices.device_type', 'meter')
                    ->where('meters.connection_group_id', $connectionGroupId)
                    ->where('addresses.owner_type', 'device')
                    ->whereIn('addresses.city_id', function ($query) use ($miniGridId) {
                        $query->select('id')
                            ->from('cities')
                            ->where('mini_grid_id', $miniGridId);
                    });
            })
            ->whereHasMorph(
                'originalTransaction',
                '*',
                static function ($q) {
                    return $q->where('status', 1);
                }
            )
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->get()->toArray();
    }

    public function getMetersByConnectionGroupForMiniGrid(
        int $miniGridId,
        int $connectionGroupId,
        string $endDate
    ): array {
        return Meter::query()
            ->selectRaw('COUNT(meters.id) as registered_connections')
            ->leftJoin('devices', 'devices.device_id', '=', 'meters.id')
            ->leftJoin('addresses', 'addresses.owner_id', '=', 'devices.id')
            ->where('meters.connection_group_id', $connectionGroupId)
            ->whereDate('meters.created_at', '<=', $endDate)
            ->whereIn('addresses.city_id', function ($query) use ($miniGridId) {
                $query->select('id')
                    ->from('cities')
                    ->where('mini_grid_id', $miniGridId);
            })
            ->where('devices.device_type', 'meter')
            ->where('addresses.owner_type', 'device')
            ->get()->toArray();
    }

    public function getRegisteredMetersByConnectionGroupInWeeklyPeriodForMiniGrid(
        int $miniGridId,
        int $connectionGroupId,
        string $startDate,
        string $endDate
    ): array {
        return Meter::query()
            ->selectRaw('COUNT(meters.id) as registered_connections, connection_groups.name, YEARWEEK(meters.created_at, 3) as period')
            ->leftJoin('devices', 'devices.device_id', '=', 'meters.id')
            ->leftJoin('addresses', 'addresses.owner_id', '=', 'devices.id')
            ->leftJoin('connection_groups', 'connection_groups.id', '=', 'meters.connection_group_id')
            ->where('meters.connection_group_id', $connectionGroupId)
            ->where('devices.device_type', 'meter')
            ->where('addresses.owner_type', 'device')
            ->whereIn('addresses.city_id', function ($query) use ($miniGridId) {
                $query->select('city_id')
                    ->from('cities')
                    ->where('mini_grid_id', $miniGridId);
            })
            ->whereBetween(DB::raw('DATE(meters.created_at)'), [$startDate, $endDate])
            ->get()->toArray();
    }

    public function getRegisteredMetersByConnectionGroupInWeeklyPeriodForCluster(
        int $clusterId,
        int $connectionGroupId,
        string $startDate,
        string $endDate
    ): array {
        return Meter::query()
            ->selectRaw('COUNT(meters.serial_number) as registered_connections, connection_groups.name, YEARWEEK(meter_parameters.created_at, 3) as period')
            ->leftJoin('devices', 'devices.device_id', '=', 'meters.id')
            ->leftJoin('addresses', 'addresses.owner_id', '=', 'devices.id')
            ->leftJoin('connection_groups', 'connection_groups.id', '=', 'meters.connection_group_id')
            ->where('meters.connection_group_id', $connectionGroupId)
            ->where('devices.device_type', 'meter')
            ->where('addresses.owner_type', 'device')
            ->whereIn('addresses.city_id', function ($query) use ($clusterId) {
                $query->select('city_id')
                    ->from('cities')
                    ->where('cluster_id', $clusterId);
            })
            ->whereBetween(DB::raw('DATE(meters.created_at)'), [$startDate, $endDate])
            ->get()->toArray();
    }

    public function getMetersByConnectionGroupForCluster(
        int $clusterId,
        int $connectionGroupId,
        string $endDate
    ): array {
        return Meter::query()
            ->selectRaw('COUNT(meters.id) as registered_connections')
            ->leftJoin('devices', 'devices.device_id', '=', 'meters.id')
            ->leftJoin('addresses', 'addresses.owner_id', '=', 'devices.id')
            ->where('meters.connection_group_id', $connectionGroupId)
            ->whereDate('meters.created_at', '<=', $endDate)
            ->whereIn('addresses.city_id', function ($query) use ($clusterId) {
                $query->select('id')
                    ->from('cities')
                    ->where('cluster_id', $clusterId);
            })
            ->where('devices.device_type', 'meter')
            ->where('addresses.owner_type', 'device')
            ->get()->toArray();
    }
}
