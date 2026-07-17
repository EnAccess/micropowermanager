<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentAssignedAppliances;
use App\Models\AgentBalanceHistory;
use App\Models\AgentCharge;
use App\Models\AgentCommission;
use App\Models\AgentReceipt;
use App\Models\Transaction\AgentTransaction;
use App\Services\Interfaces\IAssociative;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

/**
 * @implements IBaseService<AgentBalanceHistory>
 * @implements IAssociative<AgentBalanceHistory>
 */
class AgentBalanceHistoryService implements IBaseService, IAssociative {
    /** @use HasCrudOperations<AgentBalanceHistory> */
    use HasCrudOperations;

    public const TYPE_BALANCE = 'balance';
    public const TYPE_COMMISSION = 'commission';

    public const BALANCE_TRIGGER_TYPES = [
        AgentTransaction::RELATION_NAME,
        AgentAssignedAppliances::RELATION_NAME,
        AgentCharge::RELATION_NAME,
        AgentReceipt::RELATION_NAME,
    ];

    public function __construct(
        private AgentBalanceHistory $agentBalanceHistory,
        private PeriodService $periodService,
    ) {}

    protected function crudModel(): AgentBalanceHistory {
        return $this->agentBalanceHistory;
    }

    /**
     * @return Collection<int, AgentBalanceHistory>|LengthAwarePaginator<int, AgentBalanceHistory>
     */
    public function getAll(
        ?int $limit = null,
        ?int $agentId = null,
        ?string $type = null,
    ): Collection|LengthAwarePaginator {
        $query = $this->agentBalanceHistory->newQuery();
        if ($agentId) {
            $query->where('agent_id', $agentId);
        }
        if ($type === self::TYPE_COMMISSION) {
            $query->where('trigger_type', AgentCommission::RELATION_NAME);
        } elseif ($type === self::TYPE_BALANCE) {
            $query->whereIn('trigger_type', self::BALANCE_TRIGGER_TYPES);
        }
        if ($limit) {
            return $query->latest()->paginate($limit);
        }

        return $query->latest()->get();
    }

    /**
     * @param array<string, mixed> $agentBalanceHistoryData
     */
    public function make(array $agentBalanceHistoryData): AgentBalanceHistory {
        return $this->agentBalanceHistory->newQuery()->make($agentBalanceHistoryData);
    }

    public function save($agentBalanceHistory): bool {
        return $agentBalanceHistory->save();
    }

    public function getLastAgentBalanceHistory(int $agentId): ?AgentBalanceHistory {
        return $this->agentBalanceHistory->newQuery()->where('agent_id', $agentId)->latest('id')->first();
    }

    public function getTotalAmountSinceLastVisit(int $agentBalanceHistoryId, int $agentId): float {
        return $this->agentBalanceHistory->newQuery()->where('agent_id', $agentId)
            ->where('id', '>', $agentBalanceHistoryId)
            ->whereIn('trigger_type', [AgentAssignedAppliances::RELATION_NAME, AgentTransaction::RELATION_NAME])
            ->sum('amount');
    }

    public function getTransactionAverage(Agent $agent, ?AgentReceipt $lastReceipt): float {
        $query = $this->agentBalanceHistory->newQuery()
            ->where('agent_id', $agent->id)
            ->where('trigger_type', AgentTransaction::RELATION_NAME);

        if ($lastReceipt instanceof AgentReceipt) {
            $query->where('created_at', '>', $lastReceipt->created_at);
        }

        // Use avg directly on the query
        $averageTransactionAmounts = $query->avg('amount');

        return -1 * $averageTransactionAmounts;
    }

    /**
     * @return array<string, array{balance: float, due: float}>
     */
    public function getGraphValues(Agent $agent, string $lastReceiptDate): array {
        $history = $this->agentBalanceHistory->newQuery()
            ->where('agent_id', $agent->id)
            ->where('created_at', '>=', $lastReceiptDate)
            ->orderBy('id')
            ->get();

        if ($history->isEmpty()) {
            $today = new \DateTime()->format('Y-m-d');

            return [$today => ['balance' => 0.0, 'due' => 0.0]];
        }

        // Every history row snapshots the agent's post-mutation state, so the
        // last row of each day is that day's closing position.
        $period = [];
        foreach ($history as $row) {
            $period[$row->created_at->format('Y-m-d')] = [
                'balance' => $row->available_balance,
                'due' => $row->due_to_supplier,
            ];
        }

        return $period;
    }

    /**
     * @return array<string, array{revenue: float}>
     */
    public function getAgentRevenuesWeekly(Agent $agent): array {
        $startDate = date('Y-m-d', strtotime('-3 months'));
        $endDate = date('Y-m-d');

        /** @var SupportCollection<int, object{period: string, revenue: float}> $Revenues */
        $Revenues = $this->agentBalanceHistory->newQuery()
            ->selectRaw('DATE_FORMAT(created_at,\'%Y-%u\') as period, SUM(amount) as revenue')
            ->where('trigger_type', AgentCommission::RELATION_NAME)
            // Exclude payout rows (negative) so weekly revenue reflects what was earned.
            ->where('amount', '>', 0)
            ->where('agent_id', $agent->id)
            ->groupBy(DB::connection($this->agentBalanceHistory->getConnectionName())
                ->raw('DATE_FORMAT(created_at,\'%Y-%u\')'))
            ->get();

        $p = $this->periodService->generatePeriodicList($startDate, $endDate, 'weekly', ['revenue' => 0]);

        foreach ($Revenues as $revenue) {
            $p[$revenue->period]['revenue'] = $revenue->revenue;
        }

        return $p;
    }
}
