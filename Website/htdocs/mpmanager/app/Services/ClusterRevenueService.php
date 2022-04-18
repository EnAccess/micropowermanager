<?php

namespace App\Services;

use App\Http\Services\CityService;
use App\Services\MeterService;
use App\Http\Services\PeriodService;
use App\Http\Services\RevenueService;
use App\Http\Services\TransactionService;
use App\Models\Cluster;
use App\Models\ConnectionType;
use Illuminate\Support\Facades\Cache;

class ClusterRevenueService
{
    private $meterService;

    private $transactionService;

    private $cityService;

    private $periodService;

    private $revenueService;

    /**
     * ClusterService constructor.
     *
     * @param MeterService $meterService
     * @param TransactionService $transactionService
     * @param CityService $cityService
     */
    public function __construct(
        MeterService $meterService,
        TransactionService $transactionService,
        CityService $cityService,
        PeriodService $periodService,
        RevenueService $revenueService
    ) {
        $this->meterService = $meterService;
        $this->cityService = $cityService;
        $this->transactionService = $transactionService;
        $this->periodService = $periodService;
        $this->revenueService = $revenueService;
    }


    public function getMiniGridBasedRevenue($id)
    {
        $startDate = date('Y-01-01');
        $endDate = date('Y-m-t');
        $period = 'monthly';

        //get meters in clusters
        $clusterMiniGrids = Cluster::query()->with('miniGrids')->find($id);
        $miniGrids = $clusterMiniGrids->miniGrids;
        //generate initial dataset for revenue
        $periods = $this->periodService->generatePeriodicList($startDate, $endDate, $period, ['revenue' => 0]);

        foreach ($miniGrids as $miniGridIndex => $miniGrid) {
            $totalRevenue = 0;
            $p = $periods;
            $revenues = $this->revenueService->getMiniGridsRevenueByPeriod(
                $miniGrid->id,
                [$startDate, $endDate],
                $period
            );

            foreach ($revenues as $rIndex => $revenue) {
                if ($period === 'weekMonth') {
                    $p[$revenue->period][$revenue->week]['revenue'] = $revenue->revenue;
                } elseif ($period = "monthly") {
                    $p[$revenue->period]['revenue'] += $revenue->revenue;
                }
                $totalRevenue += $revenue->revenue;
            }

            $miniGrids[$miniGridIndex]['period'] = $p;
            $miniGrids[$miniGridIndex]['totalRevenue'] = $totalRevenue;
        }

        return $miniGrids;
    }

    public function getRevenueAnalysis($id): array
    {
        $revenueAnalysis = [];
        $clusterId = $id;
        $startDate = date('Y-01-01');
        $endDate = date('Y-m-t');
        $period = 'monthly';
        $periods = $this->periodService->generatePeriodicList($startDate, $endDate, $period, 0);
        //get connection types
        $connectionTypes = ConnectionType::get();
        // get meters in mini-grids


        foreach ($connectionTypes as $connectionType) {
            if (!isset($revenueAnalysis[$connectionType->name])) {
                $revenueAnalysis[$connectionType->name] = $periods;
            }
            if (!isset($revenueAnalysis['Total'])) {
                $revenueAnalysis['Total'] = $periods;
            }

            //get meters in city with connection type
            $revenues = $this->revenueService->clustersRevenueByPeriod(
                $clusterId,
                [$startDate, $endDate],
                $period,
                $connectionType->id
            );

            foreach ($revenues as $revenue) {
                if ($period === 'monthly') {
                    $revenueAnalysis[$connectionType->name][$revenue->period] += $revenue->revenue;
                    $revenueAnalysis['Total'][$revenue->period] += $revenue->revenue;
                } elseif ($period === 'weekly') {
                    $revenueAnalysis[$connectionType->name][$revenue->week] += $revenue->revenue;
                    $revenueAnalysis['Total'][$revenue->week] += $revenue->revenue;
                } elseif ($period === 'weekMonth') {
                    $revenueAnalysis[$connectionType->name][$revenue->period][$revenue->week] += $revenue->revenue;
                    $revenueAnalysis['Total'][$revenue->period][$revenue->week] += $revenue->revenue;
                }
            }
        }
        asort($revenueAnalysis);
        return $revenueAnalysis;
    }
}
