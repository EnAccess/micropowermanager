<?php

namespace App\Observers;

use App\Models\AgentReceipt;
use App\Services\AgentBalanceHistoryService;
use App\Services\AgentReceiptDetailService;
use App\Services\AgentReceiptHistoryBalanceService;
use App\Services\AgentReceiptService;
use App\Services\AgentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class AgentReceiptObserver {
    public function __construct(
        private AgentBalanceHistoryService $agentBalanceHistoryService,
        private AgentService $agentService,
        private AgentReceiptService $agentReceiptService,
        private AgentReceiptDetailService $agentReceiptDetailService,
        private AgentReceiptHistoryBalanceService $agentReceiptHistoryBalanceService,
    ) {}

    public function created(AgentReceipt $receipt): void {
        $agentId = $receipt->agent_id;
        $agent = $this->agentService->getById($agentId);
        $due = $agent->due_to_energy_supplier ?? 0;
        $sinceLastVisit = 0;
        $lastReceipt = $this->agentReceiptService->getLastReceipt($agentId);

        if ($lastReceipt) {
            $agentBalanceHistoryId = $lastReceipt->last_controlled_balance_history_id;
            $sinceLastVisit =
                $this->agentBalanceHistoryService->getTotalAmountSinceLastVisit($agentBalanceHistoryId, $agentId);
        }
        try {
            $earlier = $this->agentReceiptDetailService->getSummary($agentId);
        } catch (ModelNotFoundException $exception) {
            $earlier = 0;
        }

        $summary = $receipt->amount - $agent->due_to_energy_supplier;
        $collected = $receipt->amount;
        $agentReceiptDetailData = [
            'agent_receipt_id' => $receipt->id,
            'due' => $due,
            'collected' => $collected,
            'since_last_visit' => $sinceLastVisit ?? 0,
            'earlier' => $earlier ?? 0,
            'summary' => $summary < 0 ? 0 : $summary,
        ];
        $this->agentReceiptDetailService->create($agentReceiptDetailData);
        $agentBalanceHistoryData = [
            'agent_id' => $agent->id,
            'amount' => $receipt->amount,
            'transaction_id' => $receipt->id,
            'available_balance' => $agent->balance,
            'due_to_supplier' => $agent->due_to_energy_supplier,
        ];
        $agentBalanceHistory = $this->agentBalanceHistoryService->make($agentBalanceHistoryData);
        $this->agentReceiptHistoryBalanceService->setAssignee($receipt);
        $this->agentReceiptHistoryBalanceService->setAssigned($agentBalanceHistory);
        $this->agentReceiptHistoryBalanceService->assign();
        $this->agentBalanceHistoryService->save($agentBalanceHistory);
    }
}
