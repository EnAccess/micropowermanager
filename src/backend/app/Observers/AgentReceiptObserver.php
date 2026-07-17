<?php

namespace App\Observers;

use App\Models\AgentReceipt;
use App\Services\AgentBalanceHistoryService;
use App\Services\AgentCommissionHistoryBalanceService;
use App\Services\AgentCommissionService;
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
        private AgentCommissionService $agentCommissionService,
        private AgentCommissionHistoryBalanceService $agentCommissionHistoryBalanceService,
    ) {}

    public function created(AgentReceipt $receipt): void {
        $agentId = $receipt->agent_id;
        $agent = $this->agentService->getById($agentId);
        $due = $agent->due_to_energy_supplier ?? 0;
        $commissionCredited = $agent->commission_revenue;
        $sinceLastVisit = 0;
        $lastReceipt = $this->agentReceiptService->getLastReceipt($agentId);

        if ($lastReceipt instanceof AgentReceipt) {
            $agentBalanceHistoryId = $lastReceipt->last_controlled_balance_history_id;
            $sinceLastVisit =
                $this->agentBalanceHistoryService->getTotalAmountSinceLastVisit($agentBalanceHistoryId, $agentId);
        }
        try {
            $earlier = $this->agentReceiptDetailService->getSummary($agentId);
        } catch (ModelNotFoundException) {
            $earlier = 0;
        }

        $summary = $receipt->amount - $due;
        $this->agentReceiptDetailService->create([
            'agent_receipt_id' => $receipt->id,
            'due' => $due,
            'collected' => $receipt->amount,
            'since_last_visit' => $sinceLastVisit,
            'earlier' => $earlier ?? 0,
            'summary' => $summary < 0 ? 0 : $summary,
            'commission_credited' => $commissionCredited,
        ]);

        // The handed-over cash plus the accrued commission are credited to the
        // agent's balance in one row; the commission payout itself is recorded
        // as an explicit negative row on the commission ledger below.
        $balanceCredit = $this->agentBalanceHistoryService->make([
            'agent_id' => $agent->id,
            'amount' => $receipt->amount + $commissionCredited,
        ]);
        $this->agentReceiptHistoryBalanceService->setAssignee($receipt);
        $this->agentReceiptHistoryBalanceService->setAssigned($balanceCredit);
        $this->agentReceiptHistoryBalanceService->assign();
        $this->agentBalanceHistoryService->save($balanceCredit);

        if ($commissionCredited > 0) {
            $commission = $this->agentCommissionService->getById($agent->agent_commission_id);
            $commissionPayout = $this->agentBalanceHistoryService->make([
                'agent_id' => $agent->id,
                'amount' => -1 * $commissionCredited,
            ]);
            $this->agentCommissionHistoryBalanceService->setAssignee($commission);
            $this->agentCommissionHistoryBalanceService->setAssigned($commissionPayout);
            $this->agentCommissionHistoryBalanceService->assign();
            $this->agentBalanceHistoryService->save($commissionPayout);
        }
    }
}
