<?php

namespace App\Services;

use App\Events\PaymentSuccessEvent;
use App\Models\Agent;
use App\Models\AgentSoldAppliance;
use App\Models\AssetPerson;
use App\Services\Interfaces\IBaseService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use MPM\Device\DeviceAddressService;
use MPM\Device\DeviceService;
use MPM\Transaction\TransactionService;

/**
 * @implements IBaseService<AgentSoldAppliance>
 */
class AgentSoldApplianceService implements IBaseService {
    public function __construct(
        private AddressesService $addressesService,
        private AddressGeographicalInformationService $addressGeographicalInformationService,
        private AgentAppliancePersonService $agentAppliancePersonService,
        private AgentAssignedApplianceHistoryBalanceService $agentAssignedApplianceHistoryBalanceService,
        private AgentAssignedApplianceService $agentAssignedApplianceService,
        private AgentBalanceHistoryService $agentBalanceHistoryService,
        private AgentCommissionHistoryBalanceService $agentCommissionHistoryBalanceService,
        private AgentCommissionService $agentCommissionService,
        private AgentService $agentService,
        private AgentSoldAppliance $agentSoldAppliance,
        private AgentTransactionService $agentTransactionService,
        private AgentTransactionTransactionService $agentTransactionTransactionService,
        private AppliancePersonService $appliancePersonService,
        private ApplianceRateService $applianceRateService,
        private AssetPerson $assetPerson,
        private DeviceAddressService $deviceAddressService,
        private DeviceService $deviceService,
        private GeographicalInformationService $geographicalInformationService,
        private PersonService $personService,
        private TransactionService $transactionService,
    ) {}

    /**
     * @param array<string, mixed> $applianceData
     */
    public function create(array $applianceData): AgentSoldAppliance {
        return $this->agentSoldAppliance->newQuery()->create($applianceData);
    }

    /**
     * @return Collection<int, AssetPerson>|LengthAwarePaginator<AssetPerson>
     */
    public function getByCustomerId(int $agentId, ?int $customerId = null): Collection|LengthAwarePaginator {
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
            ->paginate();
    }

    public function getById(int $id): AgentSoldAppliance {
        throw new \Exception('Method getById() not yet implemented.');
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update($model, array $data): AgentSoldAppliance {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete($model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }

    /**
     * @return Collection<int, AgentSoldAppliance>|LengthAwarePaginator<AgentSoldAppliance>
     */
    public function getAll(
        ?int $limit = null,
        ?int $agentId = null,
        ?int $customerId = null,
        bool $forApp = false,
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
        return $this->assetPerson->newQuery()->with(['person', 'device', 'rates', 'asset.assetType'])
            ->whereHasMorph(
                'creator',
                [Agent::class],
                function ($q) use ($agentId) {
                    $q->where('id', $agentId);
                }
            )->latest()
            ->paginate();
    }

    /**
     * @return Collection<int, Agent>
     */
    public function getAgentsByCustomerId(int $customerId): Collection {
        return Agent::whereHas('soldAppliances', function ($query) use ($customerId) {
            $query->where('person_id', $customerId);
        })->get();
    }

    /**
     * @param array<string, mixed> $requestData
     */
    public function processSaleFromRequest(AgentSoldAppliance $agentSoldAppliance, array $requestData = []): void {
        $assignedApplianceId = $agentSoldAppliance->agent_assigned_appliance_id;
        $assignedAppliance = $this->agentAssignedApplianceService->getById($assignedApplianceId);
        $appliance = $assignedAppliance->appliance()->first();
        $agent = $this->agentService->getById($assignedAppliance->agent_id);
        $deviceSerial = $requestData['device_serial'] ?? null;

        // create agent transaction
        $agentTransactionData = [
            'agent_id' => $agent->id,
            'mobile_device_id' => $agent->mobile_device_id,
            'status' => 1,
        ];
        $agentTransaction = $this->agentTransactionService->create($agentTransactionData);

        // assign agent transaction to transaction
        $transactionData = [
            'amount' => $requestData['down_payment'] ?: 0,
            'sender' => 'Agent-'.$agent->id,
            'message' => $deviceSerial ?? '-',
            'type' => 'deferred_payment',
        ];

        $transaction = $this->transactionService->make($transactionData);
        $this->agentTransactionTransactionService->setAssignee($agentTransaction);
        $this->agentTransactionTransactionService->setAssigned($transaction);
        $this->agentTransactionTransactionService->assign();
        $this->transactionService->save($transaction);

        // assign agent to appliance person
        $appliancePersonData = [
            'person_id' => $requestData['person_id'],
            'first_payment_date' => Carbon::parse($requestData['first_payment_date'])->toDateString(),
            'rate_count' => $requestData['tenure'],
            'total_cost' => $assignedAppliance->cost,
            'down_payment' => $requestData['down_payment'],
            'asset_id' => $assignedAppliance->appliance->id,
            'device_serial' => $deviceSerial,
        ];

        $appliancePerson = $this->appliancePersonService->make($appliancePersonData);
        $this->agentAppliancePersonService->setAssignee($agent);
        $this->agentAppliancePersonService->setAssigned($appliancePerson);
        $this->agentAppliancePersonService->assign();
        $this->appliancePersonService->save($appliancePerson);

        if ($deviceSerial) {
            $addressFromCustomer = $appliancePerson->person()->first()->addresses()->first();
            $addressData = $requestData['address'] ?? ['street' => $addressFromCustomer->street, 'city_id' => $addressFromCustomer->city_id];
            $points = $requestData['points'] ?? $addressFromCustomer->geo()->first()->points;
            $device = $this->deviceService->getBySerialNumber($deviceSerial);
            $this->deviceService->update($device, ['person_id' => $requestData['person_id']]);
            $address = $this->addressesService->make([
                'street' => $addressData['street'],
                'city_id' => $addressData['city_id'],
            ]);

            $this->deviceAddressService->setAssigned($address);
            $this->deviceAddressService->setAssignee($device);
            $this->deviceAddressService->assign();
            $this->addressesService->save($address);

            $geoInfo = $this->geographicalInformationService->make([
                'points' => $points,
            ]);

            $this->addressGeographicalInformationService->setAssigned($geoInfo);
            $this->addressGeographicalInformationService->setAssignee($address);
            $this->addressGeographicalInformationService->assign();
            $this->geographicalInformationService->save($geoInfo);
        }

        // initalize appliance Rates
        $buyer = $this->personService->getById($appliancePerson->person_id);
        $this->applianceRateService->create($appliancePerson);

        if ($appliancePerson->down_payment > 0) {
            $applianceRate = $this->applianceRateService->getDownPaymentAsAssetRate($appliancePerson);
            event(new PaymentSuccessEvent(
                amount: $transaction->amount,
                paymentService: $transaction->original_transaction_type === 'cash_transaction' ? 'web' : 'agent',
                paymentType: 'down payment',
                sender: $transaction->sender,
                paidFor: $applianceRate,
                payer: $buyer,
                transaction: $transaction,
            ));
        }

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
