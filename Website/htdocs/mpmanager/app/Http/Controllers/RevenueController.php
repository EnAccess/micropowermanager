<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\CityService;
use App\Services\ClusterService;
use App\Services\MeterService;
use App\Services\PeriodService;
use App\Services\RevenueService;
use App\Services\TransactionService;
use App\Models\City;
use App\Models\Cluster;
use App\Models\ConnectionGroup;
use App\Models\ConnectionType;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterTariff;
use App\Models\Meter\MeterToken;
use App\Models\MiniGrid;
use App\Models\Revenue;
use App\Models\Target;
use App\Models\Transaction\Transaction;
use DateInterval;
use DatePeriod;
use DateTime;
use Illuminate\Http\Request;
use Inensus\Ticket\Models\Label;
use Inensus\Ticket\Models\Ticket;
use stdClass;

use function count;

class RevenueController extends Controller
{
    /**
     * @var Revenue
     */
    private $revenue;
    /**
     * @var ConnectionType
     */
    private $connectionType;
    /**
     * @var Target
     */
    private $target;
    /**
     * @var MeterToken
     */
    private $meterToken;
    /**
     * @var Transaction
     */
    private $transaction;
    /**
     * @var Ticket
     */
    private $ticket;
    /**
     * @var Label
     */
    private $label;
    /**
     * @var Meter
     */
    private $meter;
    /**
     * @var ClusterService
     */
    private $clusterService;
    /**
     * @var MeterService
     */
    private $meterService;
    /**
     * @var RevenueService
     */
    private $revenueService;
    /**
     * @var PeriodService
     */
    private $periodService;
    /**
     * @var Cluster
     */
    private $cluster;
    /**
     * @var ConnectionGroup
     */
    private $connectionGroup;
    /**
     * @var MiniGrid
     */
    private $miniGrid;
    /**
     * @var City
     */
    private $city;
    /**
     * @var TransactionService
     */
    private $transactionService;
    /**
     * @var CityService
     */
    private $cityService;


    /**
     * RevenueController constructor.
     *
     * @param Revenue $revenue
     * @param ConnectionType $connectionType
     * @param Target $target
     * @param MeterToken $meterToken
     * @param Transaction $transaction
     * @param Ticket $ticket
     * @param Label $label
     * @param Meter $meter
     * @param Cluster $cluster
     * @param ClusterService $clusterService
     * @param MeterService $meterService
     * @param RevenueService $revenueService
     * @param PeriodService $periodService
     * @param ConnectionGroup $connectionGroup
     * @param MiniGrid $miniGrid
     * @param City $city
     * @param TransactionService $transactionService
     * @param CityService $cityService
     */
    public function __construct(
        Revenue $revenue,
        ConnectionType $connectionType,
        Target $target,
        MeterToken $meterToken,
        Transaction $transaction,
        Ticket $ticket,
        Label $label,
        Meter $meter,
        Cluster $cluster,
        ClusterService $clusterService,
        MeterService $meterService,
        RevenueService $revenueService,
        PeriodService $periodService,
        ConnectionGroup $connectionGroup,
        MiniGrid $miniGrid,
        City $city,
        TransactionService $transactionService,
        CityService $cityService
    ) {

        $this->revenue = $revenue;
        $this->target = $target;
        $this->meterToken = $meterToken;
        $this->transaction = $transaction;
        $this->ticket = $ticket;
        $this->label = $label;
        $this->clusterService = $clusterService;
        $this->meterService = $meterService;
        $this->revenueService = $revenueService;
        $this->periodService = $periodService;
        $this->cluster = $cluster;
        $this->connectionGroup = $connectionGroup;
        $this->city = $city;
        $this->transactionService = $transactionService;
        $this->cityService = $cityService;
    }


    /**
     * Fetches tariff based analytics for a given period
     *
     * @param Request $request
     *
     * @return ApiResource
     */
    public function analysis(Request $request): ApiResource
    {

        return $request->get('base_type') === 'tariff' ?
            $this->tariffTypeBaasedAnalysis($request) :
            $this->connectionTypeBaseAnalysis($request);
    }


    private function tariffTypeBaasedAnalysis(Request $request): ApiResource
    {
        // the array which holds the final response
        $response = [];

        $startDate = $request->get('startDate') ?? '2018-01-01';
        $endDate = $request->get('endDate') ?? '2018-12-31';
        $tariffs = MeterTariff::get();

        foreach ($tariffs as $tariff) {
            //the list of meters which are belong to the current tariff
            $meters = $meters = $this->revenue->registeredMetersByTariff($tariff->id, $startDate, $endDate);
            //count of all connected customers / meters
            $totalConnections = $tariff->meterParametersCount($endDate)->first()->aggregate;
            $tariffRevenue = $this->revenue->tariffBalanceForPeriod($tariff->id, $startDate, $endDate);

            $totalRevenue = (int)$tariffRevenue[0]['total'];

            $response[] = [
                'tariff' => $tariff->name,
                'meters' => count($meters),
                'revenue' => $totalRevenue,
                'revenuePerConnection' => round($totalRevenue / $totalConnections, 0),
                'totalConnections' => $totalConnections,
            ];
        }


        return new ApiResource($response);
    }

    private function connectionTypeBaseAnalysis(Request $request): ApiResource
    {
        // the array which holds the final response
        $response = [];

        $startDate = $request->get('startDate') ?? '2018-01-01';
        $endDate = $request->get('endDate') ?? '2018-12-31';


        $connectionTypes = ConnectionGroup::get();
        foreach ($connectionTypes as $connectionType) {
            //the list of meters which are belong to the current tariff
            $meters = $meters = $this->revenue->registeredMetersByConnectionGroup(
                $connectionType->id,
                $startDate,
                $endDate
            );
            //count of all connected customers / meters


            //$totalConnections = $connectionType->meterParametersCount()->first()->aggregate;

            $tariffRevenue = $this->revenue->connectionBalanceForPeriod($connectionType->id, $startDate, $endDate);

            $totalRevenue = (int)$tariffRevenue[0]['total'];


            if (($connectionCount = $connectionType->meterParametersCount($endDate)->first()) !== null) {
                $totalConnections = $connectionCount->aggregate;
                $revenuePerConnection = round($totalRevenue / $totalConnections, 0);
            } else {
                $totalConnections = 0;
                $revenuePerConnection = $totalRevenue;
            }


            $response[] = [
                'tariff' => $connectionType->name,
                'meters' => count($meters),
                'revenue' => $totalRevenue,
                'revenuePerConnection' => $revenuePerConnection,
                'totalConnections' => $totalConnections,

            ];
        }


        return new ApiResource($response);
    }

    /**
     * @return ApiResource
     * @throws \Exception
     */
    public function ticketData($id): ApiResource
    {
        $begin = date_create('2018-08-01');
        $end = date_create();
        $end->add(new DateInterval('P1D')); //
        $i = new DateInterval('P1W');
        $period = new DatePeriod($begin, $i, $end);

        $openedTicketsWithCategories = $this->ticket->ticketsOpenedWithCategories($id);
        $closedTicketsWithCategories = $this->ticket->ticketsClosedWithCategories($id);

        $ticketCategories = $this->label->all();

        $result = [];
        $result['categories'] = $ticketCategories->toArray();
        foreach ($period as $d) {
            $day = $d->format('o-W');
            foreach ($ticketCategories as $tC) {
                $result[$day][$tC->label_name]['opened'] = 0;
                $result[$day][$tC->label_name]['closed'] = 0;
            }
        }

        foreach ($closedTicketsWithCategories as $closedTicketsWithCategory) {
            $date = $this->reformatPeriod($closedTicketsWithCategory["period"]);
            $result[$date][$closedTicketsWithCategory["label_name"]]["closed"]
                = $closedTicketsWithCategory["closed_tickets"];
        }

        foreach ($openedTicketsWithCategories as $openedTicketsWithCategory) {
            $date = $this->reformatPeriod($openedTicketsWithCategory["period"]);
            $result[$date][$openedTicketsWithCategory["label_name"]]["opened"]
                = $openedTicketsWithCategory["new_tickets"];
        }

        return new ApiResource(
            $result
        );
    }

    public function trending($id, Request $request): ApiResource
    {
        // the array which holds the final response
        $startDate = $request->input('startDate') ?? date('Y-01-01');
        $endDate = $request->input('endDate') ?? date('Y-m-d');

        $cities = $this->city::where('mini_grid_id', $id)->get();
        $cityIds = implode(',', $cities->pluck('id')->toArray());

        if (!count($cities)) {
            $response = ['data' => null, 'message' => 'There is no city for this MiniGrid'];
            return new ApiResource($response);
        }

        //get list of tariffs
        $connections = ConnectionType::get();
        $connectionNames = $connections->pluck('name')->toArray();
        $initialData = array_fill_keys($connectionNames, ['revenue' => 0]);


        $tmpDate = null;
        $response = $this->periodService->generatePeriodicList(
            $startDate,
            $endDate,
            'weekly',
            $initialData
        );


        //return $response;
        foreach ($connections as $connection) {
            $tariffRevenue = $this->revenue->weeklyConnectionBalances(
                $cityIds,
                $connection->id,
                $startDate,
                $endDate
            );

            foreach ($tariffRevenue as $revenue) {
                $totalRevenue = (int)$revenue['total'];
                $date = $this->reformatPeriod($revenue['result_date']);
                $response[$date][$connection->name] = [
                    'revenue' => $totalRevenue,
                ];
            }
        }


        return new ApiResource($response);
    }

    private function reformatPeriod($period): string
    {
        return substr_replace($period, '-', 4, 0);
    }


    /**
     * @return array
     */
    private function fetchTargets($targetData): array
    {
        $formattedTarget = [];
        if (is_object($targetData) && count($targetData) >= 1) {
            foreach ($targetData as $targets) {
                foreach ($targets->subTargets as $subTarget) {
                    if (isset($formattedTarget[$subTarget->connectionType->name])) {
                        $formattedTarget[$subTarget->connectionType->name] = [
                            'new_connections' => $formattedTarget[$subTarget->connectionType->name]['new_connections'] +
                                $subTarget->new_connections,
                            'revenue' => $formattedTarget[$subTarget->connectionType->name]['revenue'] +
                                $subTarget->revenue,
                            'connected_power' => $formattedTarget[$subTarget->connectionType->name]
                                ['connected_power'] +
                                $subTarget->connected_power,
                            'energy_per_month' => $formattedTarget[$subTarget->connectionType->name]
                                ['energy_per_month'] +
                                $subTarget->energy_per_month,
                            'average_revenue_per_month' => $formattedTarget[$subTarget->connectionType->name]
                                ['average_revenue_per_month'] +
                                $subTarget->average_revenue_per_month,
                        ];
                    } else {
                        $formattedTarget[$subTarget->connectionType->name] = [
                            'new_connections' => $subTarget->new_connections,
                            'revenue' => $subTarget->revenue,
                            'connected_power' => $subTarget->connected_power,
                            'energy_per_month' => $subTarget->energy_per_month,
                            'average_revenue_per_month' => $subTarget->average_revenue_per_month,
                        ];
                    }
                }
                unset($targets->subTargets);
            }
        }

        return $formattedTarget;
    }

    /**
     * Prepares the data for revenue dashboard
     *
     * @param Request $request
     *
     * @return array|mixed
     */
    public function revenueData(Request $request)
    {
        $startDate = date('Y-m-d', strtotime($request->get('start_date') ?? '2018-01-01'));
        $endDate = date('Y-m-d', strtotime($request->get('end_date') ?? '2018-12-31'));
        $targetTypeId = $request->get('target_type_id'); // cluster or mini-grid id
        $targetType = $request->get('target_type'); // cluster or mini-grid
        if ($targetType !== 'mini-grid' && $targetType !== 'cluster') {
            throw  new \Exception('target type must either mini-grid or cluster');
        }

        //get target
        if ($targetType === 'mini-grid') {
            $targets = $this->target->targetForMiniGrid($targetTypeId, $endDate)->first();
        } else {
            $cluster = $this->cluster::find($targetTypeId);
            $miniGridIds = $cluster->miniGrids()->get()->pluck('id');
            $targets = $this->target->targetForCluster($miniGridIds, $endDate)->get();
            $target_data = $this->fetchTargets($targets);
            $targets = $targets[0];
            $targets->targets = $target_data;
        }


        $formattedTarget = [];

        if ($targets === null) { //no target defined for that mini-grid
            $targets = new stdClass();
            $connections = $this->connectionGroup->get();
            foreach ($connections as $connection) {
                $formattedTarget[$connection->name] = [
                    'new_connections' => '-',
                    'revenue' => '-',
                    'connected_power' => '-',
                    'energy_per_month' => '-',
                    'average_revenue_per_month' => '-',
                ];
            }
        } elseif ($targets !== null && $targetType === 'mini-grid') {
            foreach ($targets->subTargets as $subTarget) {
                $formattedTarget[$subTarget->connectionType->name] = [
                    'new_connections' => $subTarget->new_connections,
                    'revenue' => $subTarget->revenue,
                    'connected_power' => $subTarget->connected_power,
                    'energy_per_month' => $subTarget->energy_per_month,
                    'average_revenue_per_month' => $subTarget->average_revenue_per_month,
                ];
            }
            unset($targets->subTargets);
        }
        if ($targetType === 'mini-grid') {
            $targets->targets = $formattedTarget;
        }

        //get all types of connections
        $connectionGroups = $this->connectionGroup->select('id', 'name')->get();

        $connections = [];
        $revenues = [];
        $totalConnections = [];

        foreach ($connectionGroups as $connectionGroup) {
            if ($targetType === 'mini-grid') {
                $revenue = $this->revenue->connectionGroupForMiniGridBasedPeriod(
                    $targetTypeId,
                    $connectionGroup->id,
                    $startDate,
                    $endDate
                );
                $totalConnectionsData = $this->revenue->registeredMetersForMiniGridByConnectionGroupTill(
                    $targetTypeId,
                    $connectionGroup->id,
                    $endDate
                );
            } else {
                $revenue = $this->revenue->connectionGroupForClusterBasedPeriod(
                    $targetTypeId,
                    $connectionGroup->id,
                    $startDate,
                    $endDate
                );
                $totalConnectionsData = $this->revenue->registeredMetersForClusterByConnectionGroupTill(
                    $targetTypeId,
                    $connectionGroup->id,
                    $endDate
                );
            }


            $totalConnections[$connectionGroup->name] = $totalConnectionsData[0]["registered_connections"];

            $revenues[$connectionGroup->name] = $revenue[0]['total'] ?? 0;
            if ($targetType === 'mini-grid') {
                $connectionsData = $this->revenue->miniGridMetersByConnectionGroup(
                    $targetTypeId,
                    $connectionGroup->id,
                    $startDate,
                    $endDate
                );
            } else {
                $connectionsData = $this->revenue->clusterMetersByConnectionGroup(
                    $targetTypeId,
                    $connectionGroup->id,
                    $startDate,
                    $endDate
                );
            }
            $connections[$connectionGroup->name] = $connectionsData[0]['registered_connections'];
        }
        return new ApiResource(
            [
                'target' => $targets,
                'total_connections' => $totalConnections,
                'new_connections' => $connections,
                'revenue' => $revenues,
            ]
        );
    }

    private function addNewConnections(array $baseList, $connections): array
    {
        foreach ($connections as $connection) {
            $date = $this->reformatPeriod($connection['period']);
            $baseList[$date]['new_connections'][$connection['name']] = $connection['registered_connections'];
        }
        return $baseList;
    }


    public function addConnectionReveneues(array $baseList, $revenues): array
    {
        foreach ($revenues as $revenue) {
            if ($revenue['result_date'] === null) {
                continue;
            }
            $date = $this->reformatPeriod($revenue['result_date']);
            $baseList[$date]['revenue'][$revenue['connection']] = (int)$revenue['total'];
        }
        return $baseList;
    }


    /**
     * @return array
     */
    public function periodDifference(
        $startDate,
        $endDate,
        $periodType,
        ?array $initValue
    ): array {
        $begin = new DateTime($startDate);
        $end = new DateTime($endDate);

        $end->add(new DateInterval('P1D')); // add one day to include the end date as a day
        $interval = $this->periodicDateInterval($periodType);

        $period = new DatePeriod($begin, $interval, $end);
        $periods = [];
        foreach ($period as $dt) {
            $periodDate = $dt->format('o-W');
            $periods[$periodDate] = [
                'revenue' => $initValue,
            ];
            $periods[$periodDate]['sold_energy'] = 0;

            $periods[$periodDate]['transactions'] = [
                'revenue' => 0,
                'total' => 0,
                'average' => 0,
                'period' => 0,
            ];
            $periods[$periodDate]['tickets'] = [
                'opened' => 0,
                'closed' => [
                    'amount' => 0,
                    'avgTime' => 0,
                ],
            ];

            foreach ($initValue as $key => $value) {
                $periods[$periodDate]['target'][$key] = [
                    'revenue' => 0,
                    'connections' => 0,
                ];

                $periods[$periodDate]['new_connections'][$key] = 0;
            }
        }
        return $periods;
    }

    /**
     * Creates according to the period-type a date-interval
     *
     * @param $periodType
     *
     * @return DateInterval
     * @throws \Exception
     */
    private function periodicDateInterval($periodType)
    {
        if ($periodType === 0) { // weekly
            return new DateInterval('P1W');
        }
        return new DateInterval('P1M');
    }

    /**
     * @return array
     */
    private function connectionGroupNames($connectionTypes): array
    {
        $names = array_flip(
            $connectionTypes->pluck("name")->toArray()
        );
        return array_map(
            function () {
                return 0;
            },
            $names
        );
    }

    public function getPeriodicMiniGridsRevenue($id, Request $request): ApiResource
    {
        $startDate = $request->input('startDate') ?? date('Y-01-01');
        $endDate = $request->input('endDate') ?? date('Y-m-t');
        $period = $request->input('period') ?? 'monthly';

        //get meters in clusters
        $clusterMiniGrids = $this->clusterService->getClusterMiniGrids($id);
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
        return new ApiResource($miniGrids);
        //get revenues by cities and summarise it
    }


    /**
     * Fetch all transactions for the cluster in weekly or monthly perspective
     *
     * @param Request $request
     *
     * @return void
     */
    public function getClusterRevenueByPeriod(Request $request)
    {
        $clusterID = $request->get('clusterID');
        if ($clusterID === null) {
            return;
        }
        $startDate = $request->get('startDate') ?? '2018-01-01';
        $endDate = $request->get('endDate') ?? '2099-12-31';
        $periodType = $request->get('periodType') ?? 'monthly';


        $clusters = $this->clusterService->getClusterList(true);
        foreach ($clusters as $index => $cluster) {
            $cluster['totalRevenue'] = 0;
            foreach ($cluster->cities as $cityIndex => $city) {
                $city = $this->meterService->getMetersInCity($city);
                $meters = $city->meters;
            }
        }
    }

    /**
     * /api/revenue
     *
     * @param Request $request
     *
     * @return ApiResource
     * @throws \Exception
     */
    public function getPeriodicClustersRevenue(Request $request): ApiResource
    {
        $startDate = $request->get('startDate');
        if (!$startDate) {
            $start = new DateTime();
            $start->setDate($start->format('Y'), $start->format('n'), 1); // Normalize the day to 1
            $start->setTime(0, 0, 0); // Normalize time to midnight
            $start->sub(new DateInterval('P12M'));
            $startDate = $start->format('Y-m-d');
        }
        $endDate = $request->get('endDate') ?? date('Y-m-t');
        $period = $request->get('period') ?? 'monthly';
        //get meters in clusters
        $clusters = $this->clusterService->getClusterList(true);
        //generate initial dataset for revenue
        $periods = $this->periodService->generatePeriodicList($startDate, $endDate, $period, ['revenue' => 0]);
        //generate initial dataset for revenue
        foreach ($clusters as $clusterIndex => $cluster) {
            $totalRevenue = 0;
            $p = $periods;
            $revenues = $this->revenueService->clustersRevenueByPeriod(
                $cluster->id,
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

            $clusters[$clusterIndex]['period'] = $p;
            $clusters[$clusterIndex]['totalRevenue'] = $totalRevenue;
        }
         return new ApiResource($clusters);
         //get revenues by cities and summarise it
    }



    public function getClusterRevenue($id, Request $request): ApiResource
    {
        $clusterId = $id;
        $startDate = $request->get('startDate');
        $endDate = $request->get('endDate');
        $dateRange = [];
        if ($startDate !== null && $endDate !== null) {
            $dateRange[0] = $startDate;
            $dateRange[1] = $endDate;
        } else {
            $dateRange[0] = date('Y-m-d', strtotime('today - 31 days'));
            $dateRange[1] = date('Y-m-d', strtotime('today - 1 days'));
        }


        $cluster = $this->cluster->find($clusterId);


        $cluster->meterCount = $this->meterService->getMeterCountInCluster($cluster->id);
        $cluster->revenue = $this->transactionService->totalClusterTransactions($cluster->id, $dateRange);
        $cluster->population = $this->cityService->getClusteropulation($cluster->id);

        return new ApiResource($cluster);
    }


    public function getRevenueAnalysisForCluster($id, Request $request): ApiResource
    {
        /**
         * !!!!
         * To group revenue by city -> connection type
         * use following structure
         * $revenueAnalysis[$city->name][$connectionType->name] = $periods;
         */
        $revenueAnalysis = [];
        $clusterId = $id;
        $startDate = $request->get('startDate') ?? date('Y-01-01');
        $endDate = $request->get('endDate') ?? date('Y-m-t');
        $period = $request->get('period') ?? 'monthly';
        $periods = $this->periodService->generatePeriodicList($startDate, $endDate, $period, 0);
        // get cluster with mini-grids
        $cluster = $this->cluster::with('cities')->find($clusterId);
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
        return new ApiResource($revenueAnalysis);
    }

    public function transactionRevenuePerMiniGrid($miniGridId, Request $request): ApiResource
    {
        $startDate = $request->get('startDate') ?? date('Y-01-01');
        $endDate = $request->get('endDate') ?? date('Y-m-t');


        $miniGridMeters = $this->meterService->getMetersInMiniGrid($miniGridId);

        $revenues = $this->revenueService->getMeterTransactions(
            $miniGridMeters,
            [$startDate, $endDate]
        );
        return new ApiResource($revenues);
    }

    public function soldEnergyPerMiniGrid($miniGridId, Request $request): ApiResource
    {
        $startDate = $request->get('startDate') ?? date('Y-01-01');
        $endDate = $request->get('endDate') ?? date('Y-m-t');


        $miniGridMeters = $this->meterService->getMetersInMiniGrid($miniGridId);

        $soldEnergy = $this->revenueService->getMeterSoldEnergy(
            $miniGridMeters,
            [$startDate, $endDate]
        );

        if ($soldEnergy) {
            $energy = round($soldEnergy[0]->energy, 3);
        }
        return new ApiResource(['data' => $energy]);
    }
}
