<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AgentPerformanceMetricsService {
    public function __construct(
        private PeriodService $periodService,
    ) {}

    /**
     * @return array{
     *     metrics: \stdClass|null,
     *     top_agents: \Illuminate\Support\Collection<int, \stdClass>,
     *     period: array<string, array{agent_commissions: float, appliance_sales: int}>
     * }
     */
    public function getMetrics(?string $startDate = null, ?string $endDate = null, ?string $interval = 'monthly'): array {
        $startDate = $startDate ? Carbon::parse($startDate) : Carbon::now()->subMonths(3)->startOfDay();
        $endDate = $endDate ? Carbon::parse($endDate) : Carbon::now()->endOfDay();

        // Overall metrics
        $overall = DB::connection('tenant')
            ->table('asset_people')
            ->selectRaw('
                COUNT(DISTINCT agents.id) AS number_of_agents,
                COUNT(asset_people.id) AS sold_appliances,
                SUM(agents.commission_revenue) AS total_commission,
                ROUND(COUNT(asset_people.person_id) / NULLIF(COUNT(DISTINCT agents.id), 0), 2) AS avg_customers_per_agent
            ')
            ->leftJoin('agents', 'asset_people.creator_id', '=', 'agents.id')
            ->where('creator_type', 'agent')
            ->whereBetween('asset_people.created_at', [$startDate, $endDate])
            ->first();

        // Top 5 performing agents
        $topAgents = DB::connection('tenant')
            ->table('asset_people')
            ->selectRaw('
                people.name AS agent,
                COUNT(DISTINCT asset_people.person_id) AS customers,
                SUM(agents.commission_revenue) AS commission,
                COUNT(asset_people.id) AS sales
            ')
            ->leftJoin('agents', 'asset_people.creator_id', '=', 'agents.id')
            ->leftJoin('people', 'agents.person_id', '=', 'people.id')
            ->where('creator_type', 'agent')
            ->whereBetween('asset_people.created_at', [$startDate, $endDate])
            ->groupBy('agents.id', 'people.name')
            ->orderByDesc('sales')
            ->limit(5)
            ->get();

        // Periodic metrics
        $groupFormat = match ($interval) {
            'daily' => '%Y-%m-%d',
            'weekly' => '%x-W%v',
            'yearly' => '%Y',
            default => '%Y-%m', // monthly
        };

        $periodicData = DB::connection('tenant')
            ->table('asset_people')
            ->selectRaw("
                DATE_FORMAT(asset_people.created_at, '{$groupFormat}') as period,
                SUM(agents.commission_revenue) AS agent_commissions,
                COUNT(asset_people.id) AS appliance_sales
            ")
            ->leftJoin('agents', 'asset_people.creator_id', '=', 'agents.id')
            ->where('creator_type', 'agent')
            ->whereBetween('asset_people.created_at', [$startDate, $endDate])
            ->groupBy('period')
            ->get();

        // Fill all periods with default values
        $intervalData = $this->periodService->generatePeriodicList(
            $startDate->toDateString(),
            $endDate->toDateString(),
            $interval,
            ['agent_commissions' => 0, 'appliance_sales' => 0]
        );

        foreach ($periodicData as $row) {
            $intervalData[$row->period] = [
                'agent_commissions' => (float) $row->agent_commissions,
                'appliance_sales' => (int) $row->appliance_sales,
            ];
        }

        return [
            'metrics' => $overall,
            'top_agents' => $topAgents,
            'period' => $intervalData,
        ];
    }
}
