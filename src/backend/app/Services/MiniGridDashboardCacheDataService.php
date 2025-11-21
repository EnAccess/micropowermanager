<?php

namespace App\Services;

use App\DTO\MiniGridDashboardData;
use App\Models\City;
use App\Models\ConnectionGroup;
use App\Models\Target;
use Illuminate\Support\Facades\Cache;
use App\Models\Ticket\Ticket;
use App\Models\Ticket\TicketCategory;
use MPM\Device\MiniGridDeviceService;
use Nette\Utils\DateTime;

class MiniGridDashboardCacheDataService extends AbstractDashboardCacheDataService {
    private const CACHE_KEY_MINI_GRIDS_DATA = 'MiniGridsData';

    public function __construct(
        private MiniGridRevenueService $miniGridRevenueService,
        private MiniGridService $miniGridService,
        private Target $target,
        private ConnectionGroup $connectionGroup,
        private ConnectionGroupService $connectionGroupService,
        private City $city,
        private ConnectionTypeService $connectionTypeService,
        private PeriodService $periodService,
        private Ticket $ticket,
        private TicketCategory $label,
        private MiniGridDeviceService $miniGridDeviceService,
        private MeterRevenueService $meterRevenueService,
    ) {
        parent::__construct(self::CACHE_KEY_MINI_GRIDS_DATA);
    }

    /**
     * @param array<int, string> $dateRange
     */
    public function setData($dateRange = []): void {
        if (empty($dateRange)) {
            $startDate = date('Y-01-01'); // first day of the year
            $endDate = date('Y-m-d H:i:s', strtotime('today'));
            $dateRange[0] = $startDate;
            $dateRange[1] = $endDate;
        } else {
            [$startDate, $endDate] = $dateRange;
        }

        $miniGrids = $this->miniGridService->getAll();

        $miniGridDashboardData = [];
        foreach ($miniGrids as $miniGrid) {
            $connections = $this->connectionGroupService->getAll();
            $connectionsTypes = $this->connectionTypeService->getAll();
            $connectionNames = $connectionsTypes->pluck('name')->toArray();
            $miniGridId = $miniGrid->id;

            $soldEnergy = $this->miniGridRevenueService->getSoldEnergyById(
                $miniGridId,
                $startDate,
                $endDate,
                $this->miniGridDeviceService
            );
            $transactions = $this->miniGridRevenueService->getById(
                $miniGridId,
                $startDate,
                $endDate,
                miniGridDeviceService: $this->miniGridDeviceService
            );

            $targets = $this->target->targetForMiniGrid($miniGridId, $endDate)->first();
            $formattedTarget = [];
            foreach ($connections as $connection) {
                $formattedTarget[$connection->name] = [
                    'new_connections' => '-',
                    'revenue' => '-',
                    'connected_power' => '-',
                    'energy_per_month' => '-',
                    'average_revenue_per_month' => '-',
                ];
            }
            if ($targets !== null && isset($targets->subTargets)) {
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
            // get all types of connections
            $connectionGroups = $this->connectionGroup->select('id', 'name')->get();
            $connectionsData = [];
            $revenues = [];
            $totalConnections = [];
            foreach ($connectionGroups as $connectionGroup) {
                $revenue = $this->meterRevenueService->getConnectionGroupBasedRevenueForMiniGrid(
                    $miniGridId,
                    $connectionGroup->id,
                    $startDate,
                    $endDate
                );
                $totalConnectionsData = $this->meterRevenueService->getMetersByConnectionGroupForMiniGrid(
                    $miniGridId,
                    $connectionGroup->id,
                    $endDate
                );
                $totalConnections[$connectionGroup->name] = $totalConnectionsData[0]['registered_connections'] ?? 0;
                $revenues[$connectionGroup->name] = $revenue[0]['total'] ?? 0;

                $connectionData = $this->meterRevenueService->getRegisteredMetersByConnectionGroupInWeeklyPeriodForMiniGrid(
                    $miniGridId,
                    $connectionGroup->id,
                    $startDate,
                    $endDate
                );
                $connectionsData[$connectionGroup->name] = $connectionData[0]['registered_connections'] ?? 0;
            }

            $cities = $this->city::where('mini_grid_id', $miniGridId)->get();
            $cityIds = implode(',', $cities->pluck('id')->toArray());
            $initialData = array_fill_keys($connectionNames, ['revenue' => 0]);

            $response = $this->periodService->generatePeriodicList(
                $startDate,
                $endDate,
                'weekly',
                $initialData
            );
            foreach ($connectionsTypes as $connectionType) {
                $tariffRevenue = $this->meterRevenueService->getConnectionTypeBasedRevenueInWeeklyPeriodForCities(
                    $cityIds,
                    $connectionType->id,
                    $startDate,
                    $endDate
                )->toArray();
                foreach ($tariffRevenue as $revenue) {
                    $totalRevenue = (int) $revenue['total'];
                    $date = $this->reformatPeriod($revenue['result_date']);
                    $response[$date][$connectionType->name] = [
                        'revenue' => $totalRevenue,
                    ];
                }
            }
            // Limit ticket data to last 12 months to avoid overwhelming the frontend
            $begin = date_create($startDate);
            $end = date_create($endDate);
            $end->add(new \DateInterval('P1D'));
            $i = new \DateInterval('P1W');
            $period = new \DatePeriod($begin, $i, $end);

            $openedTicketsWithCategories = $this->ticket->ticketsOpenedWithCategories($miniGridId, $startDate, $endDate);
            $closedTicketsWithCategories = $this->ticket->ticketsClosedWithCategories($miniGridId, $startDate, $endDate);
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

            $miniGridDashboardData[] = new MiniGridDashboardData(
                miniGrid: $miniGrid,
                soldEnergy: $soldEnergy,
                transactions: $transactions,
                period: $response,
                tickets: $result,
                revenueList: [
                    'totalConnections' => $totalConnections,
                    'revenue' => $revenues,
                    'newConnections' => $connectionsData,
                    'target' => ['targets' => $formattedTarget],
                ],
            );
        }
        Cache::put(self::cacheKeyGenerator(), $miniGridDashboardData, DateTime::from('+ 1 day'));
    }
}
