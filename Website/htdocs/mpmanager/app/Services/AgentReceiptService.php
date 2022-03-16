<?php

namespace App\Services;

use App\Models\AgentReceipt;
use App\Models\AgentBalanceHistory;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Matrix\Exception;

class AgentReceiptService implements IAgentRelatedService
{
    /**
     * @return LengthAwarePaginator
     */
    public function list($agentId): LengthAwarePaginator
    {
        return AgentReceipt::with(['user', 'agent', 'history'])
            ->whereHas(
                'agent',
                function ($q) use ($agentId) {
                    $q->where('agent_id', $agentId);
                }
            )
            ->latest()->paginate();
    }

    public function listAllReceipts(): LengthAwarePaginator
    {
        return AgentReceipt::with(['user', 'agent', 'history'])->latest()->paginate();
    }

    public function listReceiptsForUser($userId): LengthAwarePaginator
    {
        return AgentReceipt::with(['user', 'agent', 'history'])
            ->whereHas(
                'user',
                function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                }
            )
            ->latest()->paginate();
    }

    /**
     * @param $userId
     * @param int $agentId
     * @param array $receiptData
     * @return Builder|Model
     */
    public function create($userId, int $agentId, array $receiptData)
    {

        $lastBalanceHistoryId = AgentBalanceHistory::where('agent_id', $agentId)->get()->last();

        return AgentReceipt::query()->create(
            [
            'agent_id' => $agentId,
            'user_id' => $userId,
            'amount' => $receiptData['amount'],
            'last_controlled_balance_history_id' => $lastBalanceHistoryId->id
            ]
        );
    }
}
