<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentAssignedAppliances;
use App\Models\AgentBalanceHistory;
use App\Models\AgentCharge;
use App\Models\AgentCommission;
use App\Models\AgentReceipt;
use App\Models\Transaction\AgentTransaction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\This;

class AgentBalanceHistoryService  implements IBaseService, IAssociative
{

    public function __construct(
        private AgentBalanceHistory $agentBalanceHistory,
        private PeriodService $periodService)
    {

    }

    public function getAll($limit = null, $agentId = null)
    {
        $query = $this->agentBalanceHistory->newQuery()
            ->whereHasMorph(
            'trigger',
            '*'
        );
        if($agentId) {
            $query->where('agent_id', $agentId);
        }
        if ($limit) {

            return $query->latest()->paginate($limit);

        }
        return  $query->latest()->get();
    }

    public function create($agentBalanceHistoryData)
    {
        return $this->agentBalanceHistory->newQuery()->create($agentBalanceHistoryData);
    }

    public function make($agentBalanceHistoryData)
    {
        return $this->agentBalanceHistory->newQuery()->make($agentBalanceHistoryData);
    }

    public function save($agentBalanceHistory)
    {
        $agentBalanceHistory->save();
    }

    public function getLastAgentBalanceHistory($agentId)
    {
        return $this->agentBalanceHistory->newQuery()->where('agent_id', $agentId)->get()->last();
    }

    public function getTotalAmountSinceLastVisit($agentBalanceHistoryId,$agentId)
    {
        return $this->agentBalanceHistory->newQuery()->where('agent_id', $agentId)
        ->where('id', '>', $agentBalanceHistoryId)
        ->whereIn('trigger_type', ['agent_appliance', 'agent_transaction'])
        ->sum('amount');
    }

    public function getTransactionAverage($agent,$lastReceipt)
    {
        if ($lastReceipt) {
            $averageTransactionAmounts = $this->agentBalanceHistory->newQuery()
                ->where('agent_id', $agent->id)
                ->where('trigger_type', 'agent_transaction')
                ->where('created_at', '>', $lastReceipt->created_at)
                ->get()
                ->avg('amount');
        } else {
            $averageTransactionAmounts = $this->agentBalanceHistory->newQuery()
                ->where('agent_id', $agent->id)
                ->where('trigger_type', 'agent_transaction')
                ->get()
                ->avg('amount');
        }
        return -1 * $averageTransactionAmounts;
    }

    public function getGraphValues($agent, $lastReceiptDate)
    {
        $periodDate = $lastReceiptDate;
        $period = $this->getPeriod($agent, $periodDate);
        $history = $this->agentBalanceHistory->newQuery()
            ->selectRaw('DATE_FORMAT(created_at,\'%Y-%m-%d\') as date,id,trigger_Type,amount,' .
                'available_balance,due_to_supplier')
            ->where('agent_id', $agent->id)
            ->where('created_at', '>=', $periodDate)
            ->groupBy(DB::connection($this->agentBalanceHistory->getConnectionName())
                ->raw('DATE_FORMAT(created_at,\'%Y-%m-%d\'),date,id,trigger_Type,amount,' .
                'available_balance,due_to_supplier'))->get();

        if (count($history) === 1 && $history[0]->trigger_Type === 'agent_receipt') {
            $period[$history[0]->date]['balance'] = -1 * ($history[0]->due_to_supplier - $history[0]->amount);
            $period[$history[0]->date]['due'] = $history[0]->due_to_supplier - $history[0]->amount;
        } elseif (count($history) === 0) {
            return json_encode(json_decode("{}", true));
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

    public function getPeriod($agent, $date): array
    {
        $days = $this->agentBalanceHistory->newQuery()->selectRaw('DATE_FORMAT(created_at,\'%Y-%m-%d\') as day')
            ->where('agent_id', $agent->id)
            ->where(
                'created_at',
                '>=',
                $date
            )->groupBy(DB::connection($this->agentBalanceHistory->getConnectionName())
                ->raw('DATE_FORMAT(created_at,\'%Y-%m-%d\')'))
            ->get();
        $period = array();
        foreach ($days as $key => $item) {
            $period[$item->day] = [
                'balance' => 0,
                'due' => 0
            ];
        }
        return $period;
    }

    public function getAgentRevenuesWeekly($agent): array
    {
        $startDate = date("Y-m-d", strtotime("-3 months"));
        $endDate = date("Y-m-d");
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

    public function getById($id)
    {
        // TODO: Implement getById() method.
    }

    public function update($model, $data)
    {
        // TODO: Implement update() method.
    }

    public function delete($model)
    {
        // TODO: Implement delete() method.
    }


}
