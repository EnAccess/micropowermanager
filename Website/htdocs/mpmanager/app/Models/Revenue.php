<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Transaction\Transaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PDO;

class Revenue extends BaseModel
{
    public function registeredMetersByTariff($tariffId, $startDate, $endDate, $limit = null)
    {
        //get meters which are registered in the given period
        $sql = 'SELECT meters.serial_number from meters' .
            ' LEFT JOIN meter_parameters on meter_parameters.meter_id = meters.id' .
            ' LEFT JOIN meter_tariffs on meter_tariffs.id = meter_parameters.tariff_id' .
            ' where meter_tariffs.id=:tariff_id and' .
            ' DATE(meters.created_at) between :startDate and :endDate';

        if ($limit !== null) {
            $sql .= ' LIMIT ' . (int)$limit;
        }
        $sth = DB::connection('shard')->getPdo()->prepare($sql);
        $sth->bindValue(':tariff_id', $tariffId, PDO::PARAM_INT);
        $sth->bindValue(':startDate', $startDate, PDO::PARAM_STR);
        $sth->bindValue(':endDate', $endDate, PDO::PARAM_STR);

        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function registeredMetersForMiniGridByConnectionGroupTill($miniGridId, int $connectionId, string $endDate)
    {
        return MeterParameter::query()
            ->selectRaw('COUNT(meter_parameters.id) as registered_connections')
            ->leftJoin('addresses', 'addresses.owner_id', '=', 'meter_parameters.id')
            ->where('meter_parameters.connection_group_id', $connectionId)
            ->whereDate('meter_parameters.created_at', '<=', $endDate)
            ->whereIn('addresses.city_id', function ($query) use ($miniGridId) {
                $query->select('id')
                    ->from('cities')
                    ->where('mini_grid_id', $miniGridId);
            })
            ->where('addresses.owner_type', 'meter_parameter')
            ->get()->toArray();

    }

    public function registeredMetersForClusterByConnectionGroupTill($clusterId, int $connectionId, string $endDate)
    {
        return MeterParameter::query()
            ->selectRaw('COUNT(meter_parameters.id) as registered_connections')
            ->leftJoin('addresses', 'addresses.owner_id', '=', 'meter_parameters.id')
            ->where('meter_parameters.connection_group_id', $connectionId)
            ->whereDate('meter_parameters.created_at', '<=', $endDate)
            ->whereIn('addresses.city_id', function ($query) use ($clusterId) {
                $query->select('city_id')
                    ->from('cities')
                    ->where('cluster_id', $clusterId);
            })
            ->where('addresses.owner_type', 'meter_parameter')
            ->get()->toArray();
    }

    public function clusterMetersByConnectionGroup($clusterId, int $connectionId, string $startDate, string $endDate)
    {
        //get meters which are registered in the given period
        return Meter::query()
            ->leftJoin('meter_parameters', 'meter_parameters.meter_id', '=', 'meters.id')
            ->leftJoin('addresses', 'addresses.owner_id', '=', 'meter_parameters.id')
            ->leftJoin('connection_groups', 'connection_groups.id', '=', 'meter_parameters.connection_group_id')
            ->where('meter_parameters.connection_group_id', $connectionId)
            ->whereBetween(DB::raw('DATE(meter_parameters.created_at)'), [$startDate, $endDate])
            ->whereIn('addresses.city_id', function ($query) use ($clusterId) {
                $query->select('city_id')
                    ->from('cities')
                    ->where('cluster_id', $clusterId);
            })
            ->where('addresses.owner_type', 'mini-grid')
            ->selectRaw('COUNT(meters.serial_number) as registered_connections, connection_groups.name, YEARWEEK(meter_parameters.created_at, 3) as period')
            ->get()->toArray();
    }

    public function miniGridMetersByConnectionGroup($miniGridId, int $connectionId, string $startDate, string $endDate)
    {
        return Meter::query()
            ->leftJoin('meter_parameters', 'meter_parameters.meter_id', '=', 'meters.id')
            ->leftJoin('addresses', 'addresses.owner_id', '=', 'meter_parameters.id')
            ->leftJoin('connection_groups', 'connection_groups.id', '=', 'meter_parameters.connection_group_id')
            ->where('meter_parameters.connection_group_id', $connectionId)
            ->where('addresses.owner_type', 'meter_parameter')
            ->where('addresses.city_id', $miniGridId)
            ->whereBetween(DB::raw('DATE(meter_parameters.created_at)'), [$startDate, $endDate])
            ->selectRaw('COUNT(meters.serial_number) as registered_connections, connection_groups.name, YEARWEEK(meter_parameters.created_at, 3) as period')
            ->get()->toArray();
    }

    /**
     * The summary of how much the meter owner spend during  the given period ( startDate -  end Date)
     */
    public function meterBalance(string $serialNumber, string $startDate, string $endDate)
    {
        $sql = 'SELECT sum(amount) as total FROM transactions' .
            ' LEFT JOIN vodacom_transactions on vodacom_transactions.id = transactions.original_transaction_id' .
            ' AND transactions.original_transaction_type ="vodacom_transaction"' .
            ' LEFT JOIN airtel_transactions on airtel_transactions.id = transactions.original_transaction_id' .
            ' AND transactions.original_transaction_type ="airtel_transaction"' .
            ' LEFT JOIN third_party_transactions on ' .
            'third_party_transactions.id = transactions.original_transaction_id' .
            ' AND transactions.original_transaction_type ="third_party_transaction"' .

            ' LEFT JOIN agent_transactions on agent_transactions.id = transactions.original_transaction_id' .
            ' AND transactions.original_transaction_type ="agent_transaction"' .
            ' WHERE transactions.message=:serialNumber' .
            ' AND( vodacom_transactions.status =1 or airtel_transactions.status = 1 or ' .
            'third_party_transactions.status=1 or agent_transactions.status=1 )' .
            ' AND DATE(transactions.created_at) between :startDate and :endDate';

        $sth = DB::connection('shard')->getPdo()->prepare($sql);
        $sth->bindValue(':serialNumber', $serialNumber, PDO::PARAM_INT);
        $sth->bindValue(':startDate', $startDate, PDO::PARAM_STR);
        $sth->bindValue(':endDate', $endDate, PDO::PARAM_STR);

        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function tariffBalanceForPeriod(int $tariffId, string $startDate, string $endDate)
    {

        $sql = 'SELECT sum(amount) as total FROM transactions' .
            ' LEFT JOIN vodacom_transactions on vodacom_transactions.id = transactions.original_transaction_id' .
            ' AND transactions.original_transaction_type ="vodacom_transaction"' .
            ' LEFT JOIN airtel_transactions on airtel_transactions.id = transactions.original_transaction_id' .
            ' LEFT JOIN third_party_transactions on ' .
            'third_party_transactions.id = transactions.original_transaction_id' .
            ' AND transactions.original_transaction_type ="third_party_transaction"' .
            ' LEFT JOIN agent_transactions on agent_transactions.id = transactions.original_transaction_id' .
            ' AND transactions.original_transaction_type ="agent_transaction"' .
            ' AND transactions.original_transaction_type ="airtel_transaction"' .
            ' WHERE transactions.message in (SELECT serial_number from meters LEFT JOIN meter_parameters ' .
            'on meter_parameters.meter_id = meters.id where meter_parameters.tariff_id=:tariffId)' .
            ' AND( vodacom_transactions.status =1 or airtel_transactions.status = 1 or ' .
            'third_party_transactions.status=1 or agent_transactions.status=1)' .
            ' AND DATE(transactions.created_at) between DATE(:startDate) and DATE(:endDate)';

        $sth = DB::connection('shard')->getPdo()->prepare($sql);
        $sth->bindValue(':tariffId', $tariffId, PDO::PARAM_INT);
        $sth->bindValue(':startDate', $startDate, PDO::PARAM_STR);
        $sth->bindValue(':endDate', $endDate, PDO::PARAM_STR);

        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function connectionBalanceForPeriod(int $connectionId, string $startDate, string $endDate)
    {
        $sql = 'SELECT sum(transactions.amount) as total FROM transactions' .
            ' LEFT JOIN vodacom_transactions on vodacom_transactions.id = transactions.original_transaction_id' .
            ' AND transactions.original_transaction_type ="vodacom_transaction"' .
            ' LEFT JOIN airtel_transactions on airtel_transactions.id = transactions.original_transaction_id' .
            ' AND transactions.original_transaction_type ="airtel_transaction"' .
            ' LEFT JOIN third_party_transactions on ' .
            'third_party_transactions.id = transactions.original_transaction_id' .
            ' AND transactions.original_transaction_type ="third_party_transaction"' .

            ' LEFT JOIN agent_transactions on agent_transactions.id = transactions.original_transaction_id' .
            ' AND transactions.original_transaction_type ="agent_transaction"' .
            ' WHERE transactions.message in (' .
            ' SELECT serial_number from meters LEFT JOIN meter_parameters on ' .
            'meter_parameters.meter_id = meters.id where meter_parameters.connection_type_id=:connectionId)' .
            ' AND( vodacom_transactions.status =1 or airtel_transactions.status = 1 or ' .
            'third_party_transactions.status=1 or agent_transactions.status=1)' .
            ' AND DATE(transactions.created_at) between DATE(:startDate) and DATE(:endDate)';

        $sth = DB::connection('shard')->getPdo()->prepare($sql);
        $sth->bindValue(':connectionId', $connectionId, PDO::PARAM_INT);
        $sth->bindValue(':startDate', $startDate, PDO::PARAM_STR);
        $sth->bindValue(':endDate', $endDate, PDO::PARAM_STR);
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }


    public function dailyTariffBalanceForPeriod(int $tariffId, string $startDate, string $endDate)
    {

        $sql = 'SELECT sum(transactions.amount) as total, ' .
            'DATE(transactions.created_at) as result_date FROM transactions' .
            ' LEFT JOIN vodacom_transactions on vodacom_transactions.id = transactions.original_transaction_id' .
            ' AND transactions.original_transaction_type ="vodacom_transaction"' .
            ' LEFT JOIN airtel_transactions on airtel_transactions.id = transactions.original_transaction_id' .
            ' AND transactions.original_transaction_type ="airtel_transaction"' .
            ' LEFT JOIN third_party_transactions on ' .
            'third_party_transactions.id = transactions.original_transaction_id' .
            ' AND transactions.original_transaction_type ="third_party_transaction"' .

            ' LEFT JOIN agent_transactions on agent_transactions.id = transactions.original_transaction_id' .
            ' AND transactions.original_transaction_type ="agent_transaction"' .
            ' WHERE transactions.message in (SELECT serial_number from meters LEFT JOIN meter_parameters' .
            ' on meter_parameters.meter_id = meters.id where meter_parameters.tariff_id=:tariffId)' .
            ' AND( vodacom_transactions.status =1 or airtel_transactions.status = 1)' .
            ' AND DATE(transactions.created_at) between :startDate and :endDate' .
            ' GROUP BY DATE(created_at)';

        $sth = DB::connection('shard')->getPdo()->prepare($sql);
        $sth->bindValue(':tariffId', $tariffId, PDO::PARAM_INT);
        $sth->bindValue(':startDate', $startDate, PDO::PARAM_STR);
        $sth->bindValue(':endDate', $endDate, PDO::PARAM_STR);

        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function weeklyConnectionBalances(string $miniGridId, int $connectionId, string $startDate, string $endDate)
    {
        return Transaction::query()
            ->selectRaw('SUM(transactions.amount) as total, YEARWEEK(transactions.created_at, 3) as result_date')
            ->whereIn('transactions.message', function ($query) use ($connectionId, $miniGridId) {
                $query->select('serial_number')
                    ->from('meters')
                    ->leftJoin('meter_parameters', 'meter_parameters.meter_id', '=', 'meters.id')
                    ->leftJoin('addresses', 'addresses.owner_id', '=', 'meter_parameters.id')
                    ->where('meter_parameters.connection_type_id', $connectionId)
                    ->where('addresses.owner_type', 'meter_parameter')
                    ->whereIn('addresses.city_id',
                        explode(',', $miniGridId));  // assuming $miniGridId is a comma-separated string
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
            ->get()->toArray();


    }


    public function connectionTypeBasedPeriod(int $connectionType, string $startDate, string $endDate)
    {
        $sql = 'SELECT sum(transactions.amount) as total, (SELECT name from connection_groups WHERE ' .
            'id  =:connectionSelect LIMIT 1 ) as connection FROM transactions' .
            ' LEFT JOIN vodacom_transactions on vodacom_transactions.id = transactions.original_transaction_id AND' .
            ' transactions.original_transaction_type ="vodacom_transaction"' .
            ' LEFT JOIN airtel_transactions on airtel_transactions.id = transactions.original_transaction_id AND' .
            ' transactions.original_transaction_type ="airtel_transaction"' .
            ' LEFT JOIN agent_transactions on agent_transactions.id = transactions.original_transaction_id AND' .
            ' transactions.original_transaction_type ="agent_transaction"' .
            ' LEFT JOIN third_party_transactions ' .
            'on third_party_transactions.id = transactions.original_transaction_id' .
            ' AND transactions.original_transaction_type ="third_party_transaction"' .
            ' WHERE transactions.message in' .
            ' (' .
            '     SELECT serial_number from meters' .
            '     LEFT JOIN meter_parameters on meter_parameters.meter_id = meters.id' .
            '     WHERE meter_parameters.connection_type_id=:connectionTypeId' .
            ')' .
            ' AND( vodacom_transactions.status =1 or airtel_transactions.status = 1 or ' .
            'third_party_transactions.status=1 or agent_transactions.status=1)' .
            ' AND DATE(transactions.created_at) between DATE(:startDate) and DATE(:endDate)';
        //.
        //'GROUP BY(' . $periodGroup . ')';

        $sth = DB::connection('shard')->getPdo()->prepare($sql);
        $sth->bindValue(':connectionTypeId', $connectionType, PDO::PARAM_INT);
        $sth->bindValue(':connectionSelect', $connectionType, PDO::PARAM_INT);
        $sth->bindValue(':startDate', $startDate, PDO::PARAM_STR);
        $sth->bindValue(':endDate', $endDate, PDO::PARAM_STR);
        //$sth->bindValue(':periodGroup', $periodGroup, \PDO::PARAM_STR);
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }

    public function connectionGroupForMiniGridBasedPeriod(
        $miniGridId,
        int $connectionGroup,
        string $startDate,
        string $endDate
    ) {
        return Transaction::query()
            ->selectRaw('SUM(transactions.amount) as total')
            ->selectSub(
                ConnectionGroup::query()
                    ->select('name')
                    ->where('id', $connectionGroup)
                    ->limit(1),
                'connection'
            )
            ->whereIn('transactions.message', function ($query) use ($connectionGroup, $miniGridId) {
                $query->select('serial_number')
                    ->from('meters')
                    ->leftJoin('meter_parameters', 'meter_parameters.meter_id', '=', 'meters.id')
                    ->leftJoin('addresses', 'addresses.owner_id', '=', 'meter_parameters.id')
                    ->where('meter_parameters.connection_group_id', $connectionGroup)
                    ->where('addresses.owner_type', 'meter_parameter')
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

    public function connectionGroupForClusterBasedPeriod(
        $clusterId,
        int $connectionGroupId,
        string $startDate,
        string $endDate
    ) {
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
                    ->leftJoin('meter_parameters', 'meter_parameters.meter_id', '=', 'meters.id')
                    ->leftJoin('addresses', 'addresses.owner_id', '=', 'meter_parameters.id')
                    ->where('meter_parameters.connection_group_id', $connectionGroupId)
                    ->where('addresses.owner_type', 'meter_parameter')
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
}
