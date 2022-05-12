<?php

/**
 * Created by PhpStorm.
 * User: kemal
 * Date: 2019-03-14
 * Time: 11:50
 */

namespace App\Services;

use App\Models\Meter\Meter;
use App\Models\Meter\MeterToken;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\AirtelTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\VodacomTransaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RevenueService extends BaseService
{

    public function __construct(private Meter $meter, private MeterToken $meter_token, private  Transaction
    $transaction)
    {
        parent::__construct([$meter, $meter_token, $transaction]);
    }


    /**
     * @param $clusterId
     * @param array $period
     * @param $periodType
     * @param null $connectionType
     * @return Collection
     */
    public function clustersRevenueByPeriod($clusterId, array $period, $periodType, $connectionType = null): Collection
    {
        if ($periodType === 'weekly' || $periodType === 'weekMonth') {
            return $this->getMeterTransactionsByWeeklyPeriod($clusterId, $period);
        }

        return $this->getClusterTransactionsByMonthlyPeriod($clusterId, $period, $connectionType);
    }

    /**
     * @param $miniGridId
     * @param array $period
     * @param $periodType
     * @return Collection
     */
    public function getMiniGridsRevenueByPeriod($miniGridId, array $period, $periodType): Collection
    {
        if ($periodType === 'weekly' || $periodType === 'weekMonth') {
            return $this->getMeterTransactionsByWeeklyPeriod($miniGridId, $period);
        }

        return $this->getMiniGridTransactionsByMonthlyPeriod($miniGridId, $period);
    }


    public function getMetersRevenueByPeriod($meters, $period, $periodType, $connectionType = null): Collection
    {
        /* returns data like
        Month-Year    Week    Revenue
        2019-1        1        34233
        2019-1        2        34233
        2019-1        3        34233*/
        if ($periodType === 'weekly' || $periodType === 'weekMonth') {
            return $this->getMeterTransactionsByWeeklyPeriod($meters, $period);
        }

        return $this->getClusterTransactionsByMonthlyPeriod($meters, $period, $connectionType);
    }

    private function getMeterTransactionsByWeeklyPeriod($meters, $period)
    {

        return $this->transaction->newQuery()
            ->selectRaw('DATE_FORMAT(created_at,\'%Y-%m\') as period ,DATE_FORMAT(created_at,\'%Y-%u\') ' .
                ' as week, SUM(amount) as revenue')
            ->whereHasMorph(
                'originalTransaction',
                '*',
                static function ($q) {
                    $q->where('status', 1);
                }
            )
            ->whereIn('message', $meters->pluck('serial_number'))
            ->whereBetween('created_at', $period)
            ->groupBy(DB::raw('DATE_FORMAT(created_at,\'%Y-%m\'),WEEKOFYEAR(created_at)'))->get();
    }

    /**
     * @param (mixed|string)[] $period
     * @param array $period
     * @return Collection|null
     */
    public function getMeterSoldEnergy($meters, array $period): ?Collection
    {

        return $this->meter_token->newQuery()
            ->selectRaw('COUNT(id) as amount, SUM(energy) as energy')
            ->whereIn('meter_id', $meters->pluck('id'))
            ->whereBetween('created_at', $period)
            ->get();
    }

    /**
     * @param (mixed|string)[] $period
     * @param array $period
     * @return Collection
     */
    public function getMeterTransactions($meters, array $period): Collection
    {

        return $this->transaction->newQuery()
            ->selectRaw('COUNT(id) as amount, SUM(amount) as revenue')
            ->whereHasMorph(
                'originalTransaction',
                '*',
                static function ($q) {
                    return $q->where('status', 1);
                }
            )
            ->whereIn('message', $meters->pluck('serial_number'))
            ->whereBetween('created_at', $period)->get();
    }


    private function getClusterTransactionsByMonthlyPeriod($clusterId, $period, $connectionType = null)
    {
        return $this->transaction->newQuery()
            ->selectRaw('DATE_FORMAT(created_at,\'%Y-%m\') as period , SUM(amount) as revenue')
            ->whereHas(
                'meter',
                function ($q) use ($clusterId, $connectionType) {
                    $q->whereHas(
                        'meterParameter',
                        function ($q) use ($clusterId, $connectionType) {
                            if ($connectionType) {
                                $q->where('connection_type_id', $connectionType);
                            }
                            $q->whereHas(
                                'address',
                                function ($q) use ($clusterId) {
                                    $q->whereHas(
                                        'city',
                                        function ($q) use ($clusterId) {
                                            $q->where('cluster_id', $clusterId);
                                        }
                                    );
                                }
                            );
                        }
                    );
                }
            )->whereHasMorph(
                'originalTransaction',
                '*',
                static function ($q) {
                    $q->where('status', 1);
                }
            )
            ->whereBetween('created_at', $period)
            ->groupBy(DB::raw('DATE_FORMAT(created_at,\'%Y-%m\')'))->get();
    }


    private function getMiniGridTransactionsByMonthlyPeriod($miniGridId, $period)
    {
        return $this->transaction->newQuery()
            ->selectRaw('DATE_FORMAT(created_at,\'%Y-%m\') as period , SUM(amount) as revenue')
            ->whereHas(
                'meter',
                function ($q) use ($miniGridId) {
                    $q->whereHas(
                        'meterParameter',
                        function ($q) use ($miniGridId) {
                            $q->whereHas(
                                'address',
                                function ($q) use ($miniGridId) {
                                    $q->whereHas(
                                        'city',
                                        function ($q) use ($miniGridId) {
                                            $q->where('mini_grid_id', $miniGridId);
                                        }
                                    );
                                }
                            );
                        }
                    );
                }
            )->whereHasMorph(
                'originalTransaction',
                '*',
                static function ($q) {
                    $q->where('status', 1);
                }
            )
            ->whereBetween('created_at', $period)
            ->groupBy(DB::raw('DATE_FORMAT(created_at,\'%Y-%m\')'))->get();
    }
}
