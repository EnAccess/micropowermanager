<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\City;
use App\Models\Cluster;
use App\Models\ConnectionGroup;
use App\Models\ConnectionType;
use App\Models\Target;
use App\Models\Transaction\Transaction;
use App\Services\MeterRevenueService;
use App\Services\PeriodService;
use App\Services\RevenueService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inensus\Ticket\Models\Ticket;
use Inensus\Ticket\Models\TicketCategory;

class RevenueController extends Controller {
    public function __construct(
        private Ticket $ticket,
        private TicketCategory $label,
        private PeriodService $periodService,
        private City $city,
        private RevenueService $revenueService,
        private MeterRevenueService $meterRevenueService,
        private Target $target,
    ) {}

    public function ticketData(int $id): ApiResource {
        $begin = date_create('2018-08-01');
        $end = date_create();
        $end->add(new \DateInterval('P1D'));
        $i = new \DateInterval('P1W');
        $period = new \DatePeriod($begin, $i, $end);

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
            $date = $this->reformatPeriod($closedTicketsWithCategory['period']);
            $result[$date][$closedTicketsWithCategory['label_name']]['closed']
                = $closedTicketsWithCategory['closed_tickets'];
        }

        foreach ($openedTicketsWithCategories as $openedTicketsWithCategory) {
            $date = $this->reformatPeriod($openedTicketsWithCategory['period']);
            $result[$date][$openedTicketsWithCategory['label_name']]['opened']
                = $openedTicketsWithCategory['new_tickets'];
        }

        return ApiResource::make($result);
    }

    public function trending(int $id, Request $request): ApiResource {
        // the array which holds the final response
        $startDate = $request->input('startDate') ?? date('Y-01-01');
        $end = $request->input('endDate') ?? date('Y-m-d');
        $endDate = Carbon::parse($end)->endOfDay();

        $cities = $this->city::query()->where('mini_grid_id', $id)->get();
        $cityIds = implode(',', $cities->pluck('id')->toArray());

        if (!count($cities)) {
            $response = ['data' => null, 'message' => 'There is no city for this MiniGrid'];

            return ApiResource::make($response);
        }

        // get list of tariffs
        $connections = ConnectionType::query()->get();
        $connectionNames = $connections->pluck('name')->toArray();
        $initialData = array_fill_keys($connectionNames, ['revenue' => 0]);

        $response = $this->periodService->generatePeriodicList(
            $startDate,
            $endDate,
            'weekly',
            $initialData
        );

        $connections->each(function (ConnectionType $connection) use ($endDate, $startDate, $cityIds, &$response) {
            $this->meterRevenueService->getConnectionTypeBasedRevenueInWeeklyPeriodForCities(
                $cityIds,
                $connection->id,
                $startDate,
                $endDate
            )->each(function (Transaction $transaction) use ($connection, &$response) {
                $totalRevenue = (int) $transaction['total'];
                $date = $this->reformatPeriod($transaction['result_date']);
                $response[$date][$connection->name] = [
                    'revenue' => $totalRevenue,
                ];
            });
        });

        return ApiResource::make($response);
    }

    /**
     * Prepares the data for revenue dashboard.
     *
     * @param Request $request
     *
     * @return ApiResource
     */
    public function revenueData(Request $request): ApiResource {
        $startDate = date('Y-m-d', strtotime($request->get('start_date') ?? '2018-01-01'));
        $endDate = Carbon::parse(date('Y-m-d', strtotime($request->get('end_date') ?? '2018-12-31')))->endOfDay();
        $targetTypeId = $request->get('target_type_id'); // cluster or mini-grid id
        $targetType = $request->get('target_type'); // cluster or mini-grid
        if ($targetType !== 'mini-grid' && $targetType !== 'cluster') {
            throw new \Exception('target type must either mini-grid or cluster');
        }

        // get target
        if ($targetType === 'mini-grid') {
            $targets = $this->target->targetForMiniGrid($targetTypeId, $endDate)->first();
        } else {
            $cluster = Cluster::query()->find($targetTypeId);
            $miniGridIds = $cluster->miniGrids()->get()->pluck('id')->toArray();
            $targets = $this->target->targetForCluster($miniGridIds, $endDate)->get();
            $target_data = $this->revenueService->fetchTargets($targets);
            $targets = $targets[0];
            $targets->setAttribute('targets', $target_data);
        }

        $formattedTarget = [];

        if ($targets === null) { // no target defined for that mini-grid
            $targets = new \stdClass();
            $connections = ConnectionGroup::query()->get();
            foreach ($connections as $connection) {
                $formattedTarget[$connection->name] = [
                    'new_connections' => '-',
                    'revenue' => '-',
                    'connected_power' => '-',
                    'energy_per_month' => '-',
                    'average_revenue_per_month' => '-',
                ];
            }
        } elseif ($targets !== null && $targetType === 'mini-grid' && isset($targets->subTargets)) {
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

        // get all types of connections
        $connectionGroups = ConnectionGroup::query()->select('id', 'name')->get();

        $connections = [];
        $revenues = [];
        $totalConnections = [];

        foreach ($connectionGroups as $connectionGroup) {
            if ($targetType === 'mini-grid') {
                $revenue = $this->meterRevenueService->getConnectionGroupBasedRevenueForMiniGrid(
                    $targetTypeId,
                    $connectionGroup->id,
                    $startDate,
                    $endDate
                );
                $totalConnectionsData = $this->meterRevenueService->getMetersByConnectionGroupForMiniGrid(
                    $targetTypeId,
                    $connectionGroup->id,
                    $endDate
                );
            } else {
                $revenue = $this->meterRevenueService->getConnectionGroupBasedRevenueForCluster(
                    $targetTypeId,
                    $connectionGroup->id,
                    $startDate,
                    $endDate
                );
                $totalConnectionsData = $this->meterRevenueService->getMetersByConnectionGroupForCluster(
                    $targetTypeId,
                    $connectionGroup->id,
                    $endDate
                );
            }

            $totalConnections[$connectionGroup->name] = $totalConnectionsData[0]['registered_connections'];

            $revenues[$connectionGroup->name] = $revenue[0]['total'] ?? 0;
            if ($targetType === 'mini-grid') {
                $connectionsData =
                    $this->meterRevenueService->getRegisteredMetersByConnectionGroupInWeeklyPeriodForMiniGrid(
                        $targetTypeId,
                        $connectionGroup->id,
                        $startDate,
                        $endDate
                    );
            } else {
                $connectionsData =
                    $this->meterRevenueService->getRegisteredMetersByConnectionGroupInWeeklyPeriodForCluster(
                        $targetTypeId,
                        $connectionGroup->id,
                        $startDate,
                        $endDate
                    );
            }
            $connections[$connectionGroup->name] = $connectionsData[0]['registered_connections'];
        }

        return ApiResource::make(
            [
                'target' => $targets,
                'total_connections' => $totalConnections,
                'new_connections' => $connections,
                'revenue' => $revenues,
            ]
        );
    }

    private function reformatPeriod(string $period): string {
        return substr_replace($period, '-', 4, 0);
    }
}
