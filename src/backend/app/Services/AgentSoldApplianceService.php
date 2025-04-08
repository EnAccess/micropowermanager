<?php

namespace App\Services;

use App\Models\Agent;
use App\Models\AgentSoldAppliance;
use App\Models\AssetPerson;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use MPM\Transaction\TransactionService;

/**
 * @implements IBaseService<AgentSoldAppliance>
 */
class AgentSoldApplianceService implements IBaseService {
    public function __construct(
        private AgentSoldAppliance $agentSoldAppliance,
        private AssetPerson $assetPerson,
        private AgentAssignedApplianceService $agentAssignedApplianceService,
        private AgentBalanceHistoryService $agentBalanceHistoryService,
        private AgentTransactionService $agentTransactionService,
        private TransactionService $transactionService,
        private AgentTransactionTransactionService $agentTransactionTransactionService,
        private AppliancePersonService $appliancePersonService,
        private AgentAppliancePersonService $agentAppliancePersonService,
        private AgentAssignedApplianceHistoryBalanceService $agentAssignedApplianceHistoryBalanceService,
        private AgentCommissionService $agentCommissionService,
        private AgentCommissionHistoryBalanceService $agentCommissionHistoryBalanceService,
        private AgentService $agentService,
    ) {}

    public function create($applianceData): AgentSoldAppliance {
        return $this->agentSoldAppliance->newQuery()->create($applianceData);
    }

    public function getById(int $agentId, ?int $customerId = null): ?AssetPerson {
        return $this->assetPerson->newQuery()->with(['person', 'device', 'rates'])
            ->whereHasMorph(
                'creator',
                [Agent::class],
                function ($q) use ($agentId) {
                    $q->where('id', $agentId);
                }
            )
            ->where('person_id', $customerId)
            ->latest()
            // Not sure why it want to return a paginate here.
            // Commenting out for now to return a singleton.
            // ->paginate();
            ->first();
    }

    public function update($model, array $data): AgentSoldAppliance {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    public function getAll(
        ?int $limit = null,
        $agentId = null,
        $customerId = null,
        $forApp = false,
    ): Collection|LengthAwarePaginator {
        if ($forApp) {
            return $this->list($agentId);
        }

        $query = $this->agentSoldAppliance->newQuery()->with([
            'assignedAppliance',
            'assignedAppliance.appliance.assetType',
            'person',
        ]);

        if ($agentId) {
            $query->whereHas(
                'assignedAppliance',
                function ($q) use ($agentId) {
                    $q->whereHas(
                        'agent',
                        function ($q) use ($agentId) {
                            $q->where('agent_id', $agentId);
                        }
                    );
                }
            );
        }
        if ($customerId) {
            $query->where('person_id', $customerId);
        }
        if ($limit) {
            return $query->latest()->paginate($limit);
        } else {
            return $query->latest()->paginate();
        }
    }

    public function list($agentId) {
        return $this->assetPerson->newQuery()->with(['person', 'device', 'rates'])
            ->whereHasMorph(
                'creator',
                [Agent::class],
                function ($q) use ($agentId) {
                    $q->where('id', $agentId);
                }
            )->latest()
            ->paginate();
    }

    public function getAgentsByCustomerId(int $customerId): Collection {
        return Agent::whereHas('soldAppliances', function ($query) use ($customerId) {
            $query->where('person_id', $customerId);
        })->get();
    }

    public function processSaleFromRequest(AgentSoldAppliance $agentSoldAppliance, array $requestData = []): void {
        $assignedApplianceId = $agentSoldAppliance->agent_assigned_appliance_id;
        $assignedAppliance = $this->agentAssignedApplianceService->getById($assignedApplianceId);
        $appliance = $assignedAppliance->appliance()->first();
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
            'amount' => $requestData['down_payment'] ?: 0,
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
            'person_id' => $requestData['person_id'],
            'first_payment_date' => $requestData['first_payment_date'],
            'rate_count' => $requestData['tenure'],
            'total_cost' => $assignedAppliance->cost,
            'down_payment' => $requestData['down_payment'],
            'asset_id' => $assignedAppliance->appliance->id,
        ];

        $appliancePerson = $this->appliancePersonService->make($appliancePersonData);
        $this->agentAppliancePersonService->setAssignee($agent);
        $this->agentAppliancePersonService->setAssigned($appliancePerson);
        $this->agentAppliancePersonService->assign();
        $this->appliancePersonService->save($appliancePerson);

        $soldApplianceDataContainer = app()->makeWith(
            'App\Misc\SoldApplianceDataContainer',
            [
                'asset' => $appliance,
                'assetType' => $appliance->assetType,
                'assetPerson' => $appliancePerson,
                'transaction' => $transaction,
            ]
        );

        event('appliance.sold', $soldApplianceDataContainer);

        // assign agent assigned appliance to agent balance history
        $agentBalanceHistoryData = [
            'agent_id' => $agent->id,
            'amount' => (-1 * $requestData['down_payment']),
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
