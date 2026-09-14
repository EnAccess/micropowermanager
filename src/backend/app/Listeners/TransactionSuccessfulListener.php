<?php

namespace App\Listeners;

use App\Events\TransactionSuccessfulEvent;
use App\Models\Agent;
use App\Models\Transaction\BasePaymentProviderTransaction;
use App\Models\Transaction\Transaction;
use App\Providers\Helpers\TransactionAdapter;
use App\Services\AgentBalanceHistoryService;

class TransactionSuccessfulListener {
    public function __construct(
        private AgentBalanceHistoryService $agentBalanceHistoryService,
    ) {}

    public function onTransactionSuccess(Transaction $transaction): void {
        $originalTransaction = $transaction->originalTransaction()->first();
        if ($originalTransaction instanceof BasePaymentProviderTransaction) {
            TransactionAdapter::getTransaction($originalTransaction)?->sendResult(true, $transaction);
        }

        $this->creditInitiatingAgent($transaction);
    }

    public function handle(TransactionSuccessfulEvent $event): void {
        $this->onTransactionSuccess($event->transaction);
    }

    /**
     * A payment an agent pushed through a payment provider never passes through their hands, so it
     * earns them their commission but must not move their balance or their risk ceiling. Writing
     * only the AgentCommission-triggered row is what makes AgentBalanceHistoryObserver move
     * commission_revenue and leave balance alone.
     *
     * Cash payments carry no agent_id — their AgentTransaction credits both ledgers through
     * AgentTransactionProvider::sendResult() — so this never double-credits them.
     */
    private function creditInitiatingAgent(Transaction $transaction): void {
        if ($transaction->agent_id === null) {
            return;
        }

        $agent = Agent::query()->find($transaction->agent_id);

        if (!$agent instanceof Agent) {
            return;
        }

        $amount = $this->commissionAmount($agent, $transaction);

        if ($amount <= 0.0) {
            return;
        }

        $this->agentBalanceHistoryService->creditCommission($agent, $transaction, $amount);
    }

    /**
     * Mirrors what the cash flows pay: energy is commissioned on the amount collected, a sale on
     * the appliance's full cost. Installments earn nothing — the sale already commissioned the
     * whole appliance, so paying again per installment would pay twice for one sale.
     */
    private function commissionAmount(Agent $agent, Transaction $transaction): float {
        $commission = $agent->commission()->first();

        if ($commission === null) {
            return 0.0;
        }

        return match ($transaction->type) {
            Transaction::TYPE_ENERGY => abs($transaction->amount * $commission->energy_commission),
            Transaction::TYPE_DOWN_PAYMENT => $this->soldApplianceCost($transaction) * $commission->appliance_commission,
            default => 0.0,
        };
    }

    /**
     * The sale the down payment belongs to, found the same way TransactionPaymentProcessor routes
     * the payment: `message` is either the device serial or the AppliancePerson id.
     */
    private function soldApplianceCost(Transaction $transaction): float {
        $appliancePerson = $transaction->paygoAppliance ?? $transaction->nonPaygoAppliance;

        return (float) ($appliancePerson->total_cost ?? 0);
    }
}
