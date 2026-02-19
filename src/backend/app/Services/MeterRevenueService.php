<?php

namespace App\Services;

use App\Models\ConnectionGroup;
use App\Models\Meter\Meter;
use App\Models\Token;
use App\Models\Transaction\Transaction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MeterRevenueService {
    public function __construct(
        private Token $token,
        private Transaction $transaction,
    ) {}

    public function getBySerialNumber(string $serialNumber): int|float {
        $tokens = $this->token->newQuery()->whereHas(
            'device',
            function ($q) use ($serialNumber) {
                $q->where('serial_number', $serialNumber);
            }
        )->pluck('transaction_id');

        return $this->transaction->newQuery()->whereIn('id', $tokens)->sum('amount');
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getConnectionGroupBasedRevenueForCluster(
        int $clusterId,
        int $connectionGroupId,
        string $startDate,
        string $endDate,
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
                    ->join('devices', function ($join) {
                        $join->on('devices.device_id', '=', 'meters.id')
                            ->where('devices.device_type', '=', 'meter');
                    })
                    ->join('people', 'people.id', '=', 'devices.person_id')
                    ->join('addresses', function ($join) {
                        $join->on('addresses.owner_id', '=', 'people.id')
                            ->where('addresses.owner_type', '=', 'person')
                            ->where('addresses.is_primary', '=', 1);
                    })
                    ->where('meters.connection_group_id', $connectionGroupId)
                    ->whereIn('addresses.city_id', function ($query) use ($clusterId) {
                        $query->select('id')
                            ->from('cities')
                            ->where('cluster_id', $clusterId);
                    });
            })
            ->whereHasMorph(
                'originalTransaction',
                '*',
                static fn ($q) => $q->where('status', 1)
            )
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->get()->toArray();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Collection<int, Transaction>
     */
    public function getConnectionTypeBasedRevenueInWeeklyPeriodForCities(
        string $cityIds,
        int $connectionId,
        string $startDate,
        string $endDate,
    ): Collection {
        return Transaction::query()
            ->selectRaw('SUM(transactions.amount) as total, YEARWEEK(transactions.created_at, 3) as result_date')
            ->whereIn('transactions.message', function ($query) use ($connectionId, $cityIds) {
                $query->select('serial_number')
                    ->from('meters')
                    ->join('devices', function ($join) {
                        $join->on('devices.device_id', '=', 'meters.id')
                            ->where('devices.device_type', '=', 'meter');
                    })
                    ->join('people', 'people.id', '=', 'devices.person_id')
                    ->join('addresses', function ($join) {
                        $join->on('addresses.owner_id', '=', 'people.id')
                            ->where('addresses.owner_type', '=', 'person')
                            ->where('addresses.is_primary', '=', 1);
                    })
                    ->where('meters.connection_type_id', $connectionId)
                    ->whereIn('addresses.city_id', explode(',', $cityIds));
            })
            ->whereHasMorph(
                'originalTransaction',
                '*',
                static fn ($q) => $q->where('status', 1)
            )
            ->whereBetween(DB::raw('DATE(transactions.created_at)'), [$startDate, $endDate])
            ->groupBy(DB::raw('YEARWEEK(transactions.created_at, 3)'))
            ->get();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getConnectionGroupBasedRevenueForMiniGrid(
        int $miniGridId,
        int $connectionGroupId,
        string $startDate,
        string $endDate,
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
                    ->join('devices', function ($join) {
                        $join->on('devices.device_id', '=', 'meters.id')
                            ->where('devices.device_type', '=', 'meter');
                    })
                    ->join('people', 'people.id', '=', 'devices.person_id')
                    ->join('addresses', function ($join) {
                        $join->on('addresses.owner_id', '=', 'people.id')
                            ->where('addresses.owner_type', '=', 'person')
                            ->where('addresses.is_primary', '=', 1);
                    })
                    ->where('meters.connection_group_id', $connectionGroupId)
                    ->whereIn('addresses.city_id', function ($query) use ($miniGridId) {
                        $query->select('id')
                            ->from('cities')
                            ->where('mini_grid_id', $miniGridId);
                    });
            })
            ->whereHasMorph(
                'originalTransaction',
                '*',
                static fn ($q) => $q->where('status', 1)
            )
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->get()->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMetersByConnectionGroupForMiniGrid(
        int $miniGridId,
        int $connectionGroupId,
        string $endDate,
    ): array {
        return Meter::query()
            ->selectRaw('COUNT(meters.id) as registered_connections')
            ->join('devices', function ($join) {
                $join->on('devices.device_id', '=', 'meters.id')
                    ->where('devices.device_type', '=', 'meter');
            })
            ->join('people', 'people.id', '=', 'devices.person_id')
            ->join('addresses', function ($join) {
                $join->on('addresses.owner_id', '=', 'people.id')
                    ->where('addresses.owner_type', '=', 'person')
                    ->where('addresses.is_primary', '=', 1);
            })
            ->where('meters.connection_group_id', $connectionGroupId)
            ->whereDate('meters.created_at', '<=', $endDate)
            ->whereIn('addresses.city_id', function ($query) use ($miniGridId) {
                $query->select('id')
                    ->from('cities')
                    ->where('mini_grid_id', $miniGridId);
            })
            ->get()
            ->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRegisteredMetersByConnectionGroupInWeeklyPeriodForMiniGrid(
        int $miniGridId,
        int $connectionGroupId,
        string $startDate,
        string $endDate,
    ): array {
        return Meter::query()
            ->selectRaw('COUNT(meters.id) as registered_connections, connection_groups.name, YEARWEEK(meters.created_at, 3) as period')
            ->join('devices', function ($join) {
                $join->on('devices.device_id', '=', 'meters.id')
                    ->where('devices.device_type', '=', 'meter');
            })
            ->join('people', 'people.id', '=', 'devices.person_id')
            ->join('addresses', function ($join) {
                $join->on('addresses.owner_id', '=', 'people.id')
                    ->where('addresses.owner_type', '=', 'person')
                    ->where('addresses.is_primary', '=', 1);
            })
            ->leftJoin('connection_groups', 'connection_groups.id', '=', 'meters.connection_group_id')
            ->where('meters.connection_group_id', $connectionGroupId)
            ->whereIn('addresses.city_id', function ($query) use ($miniGridId) {
                $query->select('id')
                    ->from('cities')
                    ->where('mini_grid_id', $miniGridId);
            })
            ->whereBetween(DB::raw('DATE(meters.created_at)'), [$startDate, $endDate])
            ->groupBy('connection_groups.name', DB::raw('YEARWEEK(meters.created_at, 3)'))
            ->get()
            ->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getRegisteredMetersByConnectionGroupInWeeklyPeriodForCluster(
        int $clusterId,
        int $connectionGroupId,
        string $startDate,
        string $endDate,
    ): array {
        return Meter::query()
            ->selectRaw('COUNT(meters.serial_number) as registered_connections, connection_groups.name, YEARWEEK(meters.created_at, 3) as period')
            ->join('devices', function ($join) {
                $join->on('devices.device_id', '=', 'meters.id')
                    ->where('devices.device_type', '=', 'meter');
            })
            ->join('people', 'people.id', '=', 'devices.person_id')
            ->join('addresses', function ($join) {
                $join->on('addresses.owner_id', '=', 'people.id')
                    ->where('addresses.owner_type', '=', 'person')
                    ->where('addresses.is_primary', '=', 1);
            })
            ->leftJoin('connection_groups', 'connection_groups.id', '=', 'meters.connection_group_id')
            ->where('meters.connection_group_id', $connectionGroupId)
            ->whereIn('addresses.city_id', function ($query) use ($clusterId) {
                $query->select('id')
                    ->from('cities')
                    ->where('cluster_id', $clusterId);
            })
            ->whereBetween(DB::raw('DATE(meters.created_at)'), [$startDate, $endDate])
            ->groupBy('connection_groups.name', DB::raw('YEARWEEK(meters.created_at, 3)'))
            ->get()
            ->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getMetersByConnectionGroupForCluster(
        int $clusterId,
        int $connectionGroupId,
        string $endDate,
    ): array {
        return Meter::query()
            ->selectRaw('COUNT(meters.id) as registered_connections')
            ->join('devices', function ($join) {
                $join->on('devices.device_id', '=', 'meters.id')
                    ->where('devices.device_type', '=', 'meter');
            })
            ->join('people', 'people.id', '=', 'devices.person_id')
            ->join('addresses', function ($join) {
                $join->on('addresses.owner_id', '=', 'people.id')
                    ->where('addresses.owner_type', '=', 'person')
                    ->where('addresses.is_primary', '=', 1);
            })
            ->where('meters.connection_group_id', $connectionGroupId)
            ->whereDate('meters.created_at', '<=', $endDate)
            ->whereIn('addresses.city_id', function ($query) use ($clusterId) {
                $query->select('id')
                    ->from('cities')
                    ->where('cluster_id', $clusterId);
            })
            ->get()
            ->toArray();
    }
}
