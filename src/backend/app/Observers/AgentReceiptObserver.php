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
use Illuminate\Support\Facades\DB;

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
        $due = max(0.0, (float) $agent->balance);
        $pendingCommission = max(0.0, (float) $agent->commission_revenue);
        $collected = (float) $receipt->amount;

        // The agent keeps their commission out of the cash they hand over, so it is
        // credited once they have settled everything they hold, and never for more
        // than the cash on the table.
        $isFullSettlement = $due > 0 && round($collected, 2) >= round($due, 2);
        $commissionCredited = $isFullSettlement ? min($pendingCommission, $collected) : 0.0;

        $previousReceipt = $this->agentReceiptService->getLastReceipt($agentId, beforeReceiptId: $receipt->id);
        $sinceLastVisit = $this->agentBalanceHistoryService->getTotalAmountSinceLastVisit(
            $previousReceipt?->last_controlled_balance_history_id,
            $agentId,
        );

        DB::connection('tenant')->transaction(function () use (
            $receipt,
            $agent,
            $due,
            $sinceLastVisit,
            $commissionCredited,
            $collected,
        ): void {
            $this->agentReceiptDetailService->create([
                'agent_receipt_id' => $receipt->id,
                'due' => $due,
                'since_last_visit' => $sinceLastVisit,
                'commission_credited' => $commissionCredited,
            ]);

            $balanceCredit = $this->agentBalanceHistoryService->make([
                'agent_id' => $agent->id,
                'amount' => -1 * $collected,
            ]);
            $this->agentReceiptHistoryBalanceService->setAssignee($receipt);
            $this->agentReceiptHistoryBalanceService->setAssigned($balanceCredit);
            $this->agentReceiptHistoryBalanceService->assign();
            $this->agentBalanceHistoryService->save($balanceCredit);

            if ($commissionCredited <= 0) {
                return;
            }

            $commission = $this->agentCommissionService->getById($agent->agent_commission_id);
            $commissionPayout = $this->agentBalanceHistoryService->make([
                'agent_id' => $agent->id,
                'amount' => -1 * $commissionCredited,
            ]);
            $this->agentCommissionHistoryBalanceService->setAssignee($commission);
            $this->agentCommissionHistoryBalanceService->setAssigned($commissionPayout);
            $this->agentCommissionHistoryBalanceService->assign();
            $this->agentBalanceHistoryService->save($commissionPayout);
        });
    }
}
