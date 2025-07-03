<?php

namespace App\Services;

use App\Models\Cluster;
use App\Models\Meter\Meter;
use App\Models\Transaction\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class ClusterRevenueService {
    public function __construct(
        private PeriodService $periodService,
        private Cluster $cluster,
        private Transaction $transaction,
    ) {}

    /**
     * @param int                         $clusterId
     * @param array{0: string, 1: string} $period
     * @param int|null                    $connectionType
     * @param int|null                    $miniGridId
     *
     * @return Collection<int, Transaction>
     */
    public function getTransactionsForMonthlyPeriodById(
        int $clusterId,
        array $period,
        ?int $connectionType = null,
        ?int $miniGridId = null,
    ): Collection|array {
        return $this->transaction->newQuery()
            ->selectRaw('DATE_FORMAT(created_at,\'%Y-%m\') as period , SUM(amount) as revenue')
            ->whereHas(
                'device',
                function ($q) use ($clusterId, $connectionType, $miniGridId) {
                    $query = $miniGridId ?
                        $q->whereHas('address', fn ($q) => $q->whereHas('city', fn ($q) => $q->where(
                            'mini_grid_id',
                            $miniGridId
                        )))
                        :
                        $q->whereHas('address', fn ($q) => $q->whereHas('city', fn ($q) => $q->where(
                            'cluster_id',
                            $clusterId
                        )));

                    if ($connectionType) {
                        $query->whereHasMorph(
                            'device',
                            Meter::class,
                            function ($q) use ($connectionType) {
                                $q->where('connection_type_id', $connectionType);
                            }
                        );
                    }

                    return $query;
                }
            )
            ->whereHasMorph(
                'originalTransaction',
                '*',
                static function ($q) {
                    $q->where('status', 1);
                }
            )
            ->whereBetween('created_at', $period)
            ->groupBy(DB::raw('DATE_FORMAT(created_at,\'%Y-%m\')'))->get();
    }

    public function getTransactionsForWeeklyPeriod(int $clusterId, array $period, ?int $connectionType = null): Collection {
        return $this->transaction->newQuery()
            ->selectRaw('DATE_FORMAT(created_at,\'%Y-%m\') as period , SUM(amount) as revenue')
            ->whereHas(
                'device',
                function ($q) use ($clusterId, $connectionType) {
                    $query = $q->whereHas('address', fn ($q) => $q->whereHas('city', fn ($q) => $q->where(
                        'cluster_id',
                        $clusterId
                    )));
                    if ($connectionType) {
                        $query->whereHasMorph(
                            'device',
                            Meter::class,
                            function ($q) use ($connectionType) {
                                $q->where('connection_type_id', $connectionType);
                            }
                        );
                    }

                    return $query;
                }
            )
            ->whereHasMorph(
                'originalTransaction',
                '*',
                static function ($q) {
                    $q->where('status', 1);
                }
            )
            ->whereBetween('created_at', $period)
            ->groupBy(DB::raw('DATE_FORMAT(created_at,\'%Y-%m\'),WEEKOFYEAR(created_at)'))->get();
    }

    public function getPeriodicRevenueForClustersOld(
        array $clusters,
        string $startDate,
        string $endDate,
        array $periods,
        string $period,
    ): array {
        foreach ($clusters as $clusterIndex => $cluster) {
            $totalRevenue = 0;
            $p = $periods;
            if ($period === 'weekly' || $period === 'weekMonth') {
                $revenues = $this->getTransactionsForWeeklyPeriod($cluster->id, [$startDate, $endDate]);
            } else {
                $revenues = $this->getTransactionsForMonthlyPeriodById($cluster->id, [$startDate, $endDate]);
            }
            foreach ($revenues as $rIndex => $revenue) {
                if ($period === 'weekMonth') {
                    $p[$revenue->period][$revenue->week]['revenue'] = $revenue->revenue;
                } elseif ($period = 'monthly') {
                    $p[$revenue->period]['revenue'] += $revenue->revenue;
                }
                $totalRevenue += $revenue->revenue;
            }

            $clusters[$clusterIndex]['period'] = $p;
            $clusters[$clusterIndex]['totalRevenue'] = $totalRevenue;
        }

        return $clusters;
    }

    public function getPeriodicRevenueForCluster(
        Cluster $cluster,
        string $startDate,
        string $endDate,
        array $periodsMonthly,
        array $periodsWeekly,
    ): array {
        $totalRevenue = 0;
        $pM = $periodsMonthly;
        $pW = $periodsWeekly;
        $weeklyRevenues = $this->getTransactionsForWeeklyPeriod($cluster->id, [$startDate, $endDate]);
        $monthlyRevenues = $this->getTransactionsForMonthlyPeriodById($cluster->id, [$startDate, $endDate]);

        foreach ($weeklyRevenues as $rIndex => $revenue) {
            $pW[$revenue->period][$revenue->week]['revenue'] = $revenue->revenue;
        }

        foreach ($monthlyRevenues as $rIndex => $revenue) {
            $pM[$revenue->period]['revenue'] += $revenue->revenue;
            $totalRevenue += $revenue->revenue;
        }

        return [
            'periodWeekly' => $pW,
            'period' => $pM,
            'totalRevenue' => $totalRevenue,
        ];
    }

    public function getRevenueAnalysisForConnectionTypesByCluser(
        string $startDate,
        string $endDate,
        string $period,
        Cluster $cluster,
        iterable $connectionTypes,
    ): array {
        $revenueAnalysis = [];
        $periods = $this->periodService->generatePeriodicList($startDate, $endDate, $period, 0);

        foreach ($connectionTypes as $connectionType) {
            if (!isset($revenueAnalysis[$connectionType->name])) {
                $revenueAnalysis[$connectionType->name] = $periods;
            }
            if (!isset($revenueAnalysis['Total'])) {
                $revenueAnalysis['Total'] = $periods;
            }

            if ($period === 'weekly' || $period === 'weekMonth') {
                $revenues = $this->getTransactionsForWeeklyPeriod($cluster->id, [$startDate, $endDate]);
            } else {
                $revenues = $this->getTransactionsForMonthlyPeriodById(
                    $cluster->id,
                    [$startDate, $endDate],
                    $connectionType->id
                );
            }

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

    public function getMonthlyRevenueAnalysisForConnectionTypesById(
        int $clusterId,
        iterable $connectionTypes,
    ): array {
        $revenueAnalysis = [];
        $startDate = date('Y-01-01');
        $endDate = date('Y-m-t');
        $period = 'monthly';
        $periods = $this->periodService->generatePeriodicList($startDate, $endDate, $period, 0);

        foreach ($connectionTypes as $connectionType) {
            if (!isset($revenueAnalysis[$connectionType->name])) {
                $revenueAnalysis[$connectionType->name] = $periods;
            }

            if (!isset($revenueAnalysis['Total'])) {
                $revenueAnalysis['Total'] = $periods;
            }

            $revenues =
                $this->getTransactionsForMonthlyPeriodById($clusterId, [$startDate, $endDate], $connectionType->id);

            foreach ($revenues as $revenue) {
                $revenueAnalysis[$connectionType->name][$revenue->period] += $revenue->revenue;
                $revenueAnalysis['Total'][$revenue->period] += $revenue->revenue;
            }
        }
        asort($revenueAnalysis);

        return $revenueAnalysis;
    }

    /**
     * @throws \Exception
     */
    public function getMonthlyMiniGridBasedRevenueById(int $clusterId) {
        $startDate = date('Y-01-01');
        $endDate = date('Y-m-t');
        $period = 'monthly';
        $clusterMiniGrids = $this->cluster->newQuery()->with('miniGrids')->find($clusterId);
        $miniGrids = $clusterMiniGrids->miniGrids;
        $periods = $this->periodService->generatePeriodicList($startDate, $endDate, $period, ['revenue' => 0]);

        foreach ($miniGrids as $miniGridIndex => $miniGrid) {
            $totalRevenue = 0;
            $p = $periods;
            $revenues = $this->getTransactionsForMonthlyPeriodById(
                $clusterId,
                [$startDate, $endDate],
                null,
                $miniGrid->id
            );

            foreach ($revenues as $rIndex => $revenue) {
                $p[$revenue->period]['revenue'] += $revenue->revenue;
                $totalRevenue += $revenue->revenue;
            }

            $miniGrids[$miniGridIndex]['period'] = $p;
            $miniGrids[$miniGridIndex]['totalRevenue'] = $totalRevenue;
        }

        return $miniGrids;
    }

    /**
     * @throws \Exception
     */
    public function getMiniGridBasedRevenueById(
        int $clusterId,
        string $startDate,
        string $endDate,
        string $period,
    ) {
        $clusterMiniGrids = $this->cluster->newQuery()->with('miniGrids')->find($clusterId);
        $miniGrids = $clusterMiniGrids->miniGrids;
        $periods = $this->periodService->generatePeriodicList($startDate, $endDate, $period, ['revenue' => 0]);

        foreach ($miniGrids as $miniGridIndex => $miniGrid) {
            $totalRevenue = 0;
            $p = $periods;
            if ($period === 'weekly' || $period === 'weekMonth') {
                $revenues = $this->getTransactionsForWeeklyPeriod($clusterId, [$startDate, $endDate]);
            } else {
                $revenues = $this->getTransactionsForMonthlyPeriodById($clusterId, [$startDate, $endDate]);
            }

            foreach ($revenues as $rIndex => $revenue) {
                if ($period === 'weekMonth') {
                    $p[$revenue->period][$revenue->week]['revenue'] = $revenue->revenue;
                } elseif ($period = 'monthly') {
                    $p[$revenue->period]['revenue'] += $revenue->revenue;
                }
                $totalRevenue += $revenue->revenue;
            }

            $miniGrids[$miniGridIndex]['period'] = $p;
            $miniGrids[$miniGridIndex]['totalRevenue'] = $totalRevenue;
        }

        return $miniGrids;
    }

    public function setDatesForRequest(string $startDate, string $endDate): array {
        if (!$startDate) {
            $start = new \DateTime();
            $year = (int) $start->format('Y');
            $month = (int) $start->format('n');
            $start->setDate($year, $month, 1); // Normalize the day to 1
            $start->setTime(0, 0, 0); // Normalize time to midnight
            $start->sub(new \DateInterval('P12M'));
            $startDate = $start->format('Y-m-d');
        }
        $endDate = $endDate ?? date('Y-m-t');

        return ['startDate' => $startDate, 'endDate' => $endDate];
    }

    public function setDateRangeForRequest(string $startDate, string $endDate): array {
        $dateRange = [];
        if ($startDate !== null && $endDate !== null) {
            $dateRange[0] = $startDate;
            $dateRange[1] = $endDate;
        } else {
            $dateRange[0] = date('Y-m-d', strtotime('today - 31 days'));
            $dateRange[1] = date('Y-m-d', strtotime('today - 1 days'));
        }

        return $dateRange;
    }
}
