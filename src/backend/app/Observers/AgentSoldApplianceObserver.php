<?php

namespace App\Observers;

use App\Models\AgentSoldAppliance;
use App\Services\AgentAppliancePersonService;
use App\Services\AgentAssignedApplianceHistoryBalanceService;
use App\Services\AgentAssignedApplianceService;
use App\Services\AgentBalanceHistoryService;
use App\Services\AgentCommissionHistoryBalanceService;
use App\Services\AgentCommissionService;
use App\Services\AgentService;
use App\Services\AgentTransactionService;
use App\Services\AgentTransactionTransactionService;
use App\Services\AppliancePersonService;
use MPM\Transaction\TransactionService;

class AgentSoldApplianceObserver
{
    public function __construct(
        private AgentBalanceHistoryService $agentBalanceHistoryService,
        private AgentAssignedApplianceService $agentAssignedApplianceService,
        private AgentService $agentService,
        private AgentTransactionService $agentTransactionService,
        private TransactionService $transactionService,
        private AgentTransactionTransactionService $agentTransactionTransactionService,
        private AppliancePersonService $appliancePersonService,
        private AgentAppliancePersonService $agentAppliancePersonService,
        private AgentAssignedApplianceHistoryBalanceService $agentAssignedApplianceHistoryBalanceService,
        private AgentCommissionService $agentCommissionService,
        private AgentCommissionHistoryBalanceService $agentCommissionHistoryBalanceService,
    ) {
    }

    public function created(AgentSoldAppliance $agentSoldAppliance): void
    {
        if (request()->all()) {
            $this->processSaleIfIsNotCreatedByFactory($agentSoldAppliance);
        }
    }

    private function processSaleIfIsNotCreatedByFactory($agentSoldAppliance)
    {
        $assignedApplianceId = $agentSoldAppliance->agent_assigned_appliance_id;
        $assignedAppliance = $this->agentAssignedApplianceService->getById($assignedApplianceId);
        $appliance = $assignedAppliance->applianceType()->first();
        $agent = $this->agentService->getById($assignedAppliance->agent_id);

        // create agent transaction
        $agentTransactionData = [
            'agent_id' => $agent->id,
            'device_id' => $agent->device_id,
            'status' => 1,
        ];
        $agentTransaction = $this->agentTransactionService->create($agentTransactionData);

        // assign agent transaction to transaction
        $transactionData = [
            'amount' => request()->input('down_payment') ?: 0,
            'sender' => $agent->device_id,
            'message' => '-',
        ];

        $transaction = $this->transactionService->make($transactionData);
        $this->agentTransactionTransactionService->setAssignee($agentTransaction);
        $this->agentTransactionTransactionService->setAssigned($transaction);
        $this->agentTransactionTransactionService->assign();
        $this->transactionService->save($transaction);

        // assign agent to appliance person
        $appliancePersonData = [
            'person_id' => request()->input('person_id'),
            'first_payment_date' => request()->input('first_payment_date'),
            'rate_count' => request()->input('tenure'),
            'total_cost' => $assignedAppliance->cost,
            'down_payment' => request()->input('down_payment'),
            'asset_type_id' => $assignedAppliance->applianceType->id,
        ];
        $appliancePerson = $this->appliancePersonService->make($appliancePersonData);
        $this->agentAppliancePersonService->setAssignee($agent);
        $this->agentAppliancePersonService->setAssigned($appliancePerson);
        $this->agentAppliancePersonService->assign();
        $this->appliancePersonService->save($appliancePerson);

        $soldApplianceDataContainer = app()->makeWith(
            'App\Misc\SoldApplianceDataContainer',
            [
                'assetType' => $appliance,
                'assetPerson' => $appliancePerson,
                'transaction' => $transaction,
            ]
        );

        event('appliance.sold', $soldApplianceDataContainer);

        // assign agent assigned appliance to agent balance history
        $agentBalanceHistoryData = [
            'agent_id' => $agent->id,
            'amount' => (-1 * request()->input('down_payment')),
            'transaction_id' => $transaction->id,
            'available_balance' => $agent->balance,
            'due_to_supplier' => $agent->due_to_energy_supplier,
        ];
        $agentBalanceHistory = $this->agentBalanceHistoryService->make($agentBalanceHistoryData);
        $this->agentAssignedApplianceHistoryBalanceService->setAssignee($assignedAppliance);
        $this->agentAssignedApplianceHistoryBalanceService->setAssigned($agentBalanceHistory);
        $this->agentAssignedApplianceHistoryBalanceService->assign();
        $this->agentBalanceHistoryService->save($agentBalanceHistory);

        // create agent commission
        $agentCommission = $this->agentCommissionService->getById($agent->agent_commission_id);

        // assign agent commission to agent balance history
        $agentBalanceHistoryData = [
            'agent_id' => $agent->id,
            'amount' => ($assignedAppliance->cost * $agentCommission->appliance_commission),
            'transaction_id' => $transaction->id,
            'available_balance' => $agent->commission_revenue,
            'due_to_supplier' => $agent->due_to_energy_supplier,
        ];
        $agentBalanceHistory = $this->agentBalanceHistoryService->make($agentBalanceHistoryData);
        $this->agentCommissionHistoryBalanceService->setAssignee($agentCommission);
        $this->agentCommissionHistoryBalanceService->setAssigned($agentBalanceHistory);
        $this->agentCommissionHistoryBalanceService->assign();
        $this->agentBalanceHistoryService->save($agentBalanceHistory);
    }
}
