<?php

namespace App\Services;

use App\Models\AgentBalanceHistory;
use App\Services\Interfaces\IAssociative;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * @implements IBaseService<AgentBalanceHistory>
 * @implements IAssociative<AgentBalanceHistory>
 */
class AgentBalanceHistoryService implements IBaseService, IAssociative {
    public function __construct(
        private AgentBalanceHistory $agentBalanceHistory,
        private PeriodService $periodService,
    ) {}

    public function getAll(?int $limit = null, ?int $agentId = null): Collection|LengthAwarePaginator {
        $query = $this->agentBalanceHistory->newQuery()
            ->whereHasMorph(
                'trigger',
                '*'
            );
        if ($agentId) {
            $query->where('agent_id', $agentId);
        }
        if ($limit) {
            return $query->latest()->paginate($limit);
        }

        return $query->latest()->get();
    }

    public function create(array $agentBalanceHistoryData): AgentBalanceHistory {
        return $this->agentBalanceHistory->newQuery()->create($agentBalanceHistoryData);
    }

    public function make(array $agentBalanceHistoryData): AgentBalanceHistory {
        return $this->agentBalanceHistory->newQuery()->make($agentBalanceHistoryData);
    }

    public function save($agentBalanceHistory): bool {
        return $agentBalanceHistory->save();
    }

    public function getLastAgentBalanceHistory($agentId) {
        return $this->agentBalanceHistory->newQuery()->where('agent_id', $agentId)->latest('id')->first();
    }

    public function getTotalAmountSinceLastVisit($agentBalanceHistoryId, $agentId) {
        return $this->agentBalanceHistory->newQuery()->where('agent_id', $agentId)
            ->where('id', '>', $agentBalanceHistoryId)
            ->whereIn('trigger_type', ['agent_appliance', 'agent_transaction'])
            ->sum('amount');
    }

    public function getTransactionAverage($agent, $lastReceipt) {
        $query = $this->agentBalanceHistory->newQuery()
            ->where('agent_id', $agent->id)
            ->where('trigger_type', 'agent_transaction');

        if ($lastReceipt) {
            $query->where('created_at', '>', $lastReceipt->created_at);
        }

        // Use avg directly on the query
        $averageTransactionAmounts = $query->avg('amount');

        return -1 * $averageTransactionAmounts;
    }

    public function getGraphValues($agent, $lastReceiptDate) {
        $periodDate = $lastReceiptDate;
        $period = $this->getPeriod($agent, $periodDate);
        $history = $this->agentBalanceHistory->newQuery()
            ->selectRaw('DATE_FORMAT(created_at,\'%Y-%m-%d\') as date,id,trigger_Type,amount,'.
                'available_balance,due_to_supplier')
            ->where('agent_id', $agent->id)
            ->where('created_at', '>=', $periodDate)
            ->groupBy(DB::connection($this->agentBalanceHistory->getConnectionName())
                ->raw('DATE_FORMAT(created_at,\'%Y-%m-%d\'),date,id,trigger_Type,amount,'.
                    'available_balance,due_to_supplier'))->get();

        if (count($history) === 1 && $history[0]->trigger_Type === 'agent_receipt') {
            $period[$history[0]->date]['balance'] = -1 * ($history[0]->due_to_supplier - $history[0]->amount);
            $period[$history[0]->date]['due'] = $history[0]->due_to_supplier - $history[0]->amount;
        } elseif (count($history) === 0) {
            $date = new \DateTime();
            $key = $date->format('Y-m-d');
            $period[$key] = [
                'balance' => 0,
                'due' => 0,
            ];

            return $period;
        } else {
            foreach ($period as $key => $value) {
                foreach ($history as $h) {
                    if ($key === $h->date) {
                        $lastRow = $history->where('trigger_Type', '!=', 'agent_commission')
                            ->where('trigger_Type', '!=', 'agent_receipt')
                            ->where(
                                'date',
                                '=',
                                $h->date
                            )->last();

                        $lastComissionRow = $history->where('trigger_Type', '=', 'agent_commission')
                            ->where('trigger_Type', '!=', 'agent_receipt')
                            ->where(
                                'date',
                                '=',
                                $h->date
                            )->last();
                        $period[$key]['balance'] = $lastRow !== null ?
                            $lastRow->amount + $lastRow->available_balance : null;
                        $period[$key]['due'] = $lastRow !== null ? ((-1 * $lastRow->amount) + $lastRow->due_to_supplier)
                            - (1 * $lastComissionRow->amount) : null;
                    }
                }
            }
        }

        return $period;
    }

    public function getPeriod($agent, $date): array {
        $days = $this->agentBalanceHistory->newQuery()->selectRaw('DATE_FORMAT(created_at,\'%Y-%m-%d\') as day')
            ->where('agent_id', $agent->id)
            ->where(
                'created_at',
                '>=',
                $date
            )->groupBy(DB::connection($this->agentBalanceHistory->getConnectionName())
            ->raw('DATE_FORMAT(created_at,\'%Y-%m-%d\')'))
            ->get();
        $period = [];
        foreach ($days as $key => $item) {
            $period[$item->day] = [
                'balance' => 0,
                'due' => 0,
            ];
        }

        return $period;
    }

    public function getAgentRevenuesWeekly($agent): array {
        $startDate = date('Y-m-d', strtotime('-3 months'));
        $endDate = date('Y-m-d');
        $Revenues = $this->agentBalanceHistory->newQuery()
            ->selectRaw('DATE_FORMAT(created_at,\'%Y-%u\') as period, SUM(amount) as revenue')
            ->where('trigger_type', 'agent_commission')
            ->where('agent_id', $agent->id)
            ->groupBy(DB::connection($this->agentBalanceHistory->getConnectionName())
                ->raw('DATE_FORMAT(created_at,\'%Y-%u\')'))
            ->get();

        $p = $this->periodService->generatePeriodicList($startDate, $endDate, 'weekly', ['revenue' => 0]);
        foreach ($Revenues as $rIndex => $revenue) {
            $p[$revenue->period]['revenue'] = $revenue->revenue;
        }

        return $p;
    }

    public function getById($id): AgentBalanceHistory {
        throw new \Exception('Method getById() not yet implemented.');
    }

    public function update($model, $data): AgentBalanceHistory {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }
}
