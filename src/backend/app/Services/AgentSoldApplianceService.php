<?php

namespace App\Services;

use App\Events\PaymentSuccessEvent;
use App\Models\Agent;
use App\Models\AgentSoldAppliance;
use App\Models\AppliancePerson;
use App\Models\GeographicalInformation;
use App\Models\Transaction\Transaction;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<AgentSoldAppliance>
 */
class AgentSoldApplianceService implements IBaseService {
    /** @use HasCrudOperations<AgentSoldAppliance> */
    use HasCrudOperations;

    public function __construct(
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
        private AppliancePerson $appliancePerson,
        private DeviceService $deviceService,
        private PersonService $personService,
        private TransactionService $transactionService,
    ) {}

    protected function crudModel(): AgentSoldAppliance {
        return $this->agentSoldAppliance;
    }

    /**
     * @return Collection<int, AppliancePerson>|LengthAwarePaginator<int, AppliancePerson>
     */
    public function getByCustomerId(int $agentId, ?int $customerId = null): Collection|LengthAwarePaginator {
        return $this->appliancePerson->newQuery()->with($this->agentAppEagerLoads())
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

    /**
     * Relations the field app needs on every sold-appliance payload.
     *
     * @return array<int|string, mixed>
     */
    private function agentAppEagerLoads(): array {
        return [
            'person',
            'device',
            'rates.paymentHistories' => fn ($query) => $query
                ->select(['id', 'transaction_id', 'amount', 'paid_for_id', 'paid_for_type'])
                ->orderByDesc('id'),
        ];
    }

    /**
     * The sales an agent made, as the AppliancePerson records themselves rather than the
     * agent_sold_appliances join rows -- those carry their own ids, which do not address
     * the sold appliance detail endpoint, and their cost is the assigned stock price
     * rather than what the customer was actually charged.
     *
     * @return LengthAwarePaginator<int, AppliancePerson>
     */
    public function list(int $agentId, ?int $limit = null): LengthAwarePaginator {
        return $this->appliancePerson->newQuery()
            ->with([...$this->agentAppEagerLoads(), 'appliance.applianceType'])
            ->whereHasMorph(
                'creator',
                [Agent::class],
                function ($q) use ($agentId) {
                    $q->where('id', $agentId);
                }
            )->latest()
            ->paginate($limit);
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
     * Books an appliance sale for the agent owning the assigned appliance, no matter whether
     * the agent sold it themselves through the app or an admin recorded it for them on the web.
     *
     * @param array<string, mixed> $requestData
     */
    public function sell(array $requestData): AgentSoldAppliance {
        $agentSoldAppliance = $this->create([
            'person_id' => $requestData['person_id'],
            'agent_assigned_appliance_id' => $requestData['agent_assigned_appliance_id'],
        ]);

        $this->processSaleFromRequest($agentSoldAppliance, $requestData);

        return $agentSoldAppliance;
    }

    /**
     * @param array<string, mixed> $requestData
     */
    public function processSaleFromRequest(AgentSoldAppliance $agentSoldAppliance, array $requestData = []): void {
        $assignedApplianceId = $agentSoldAppliance->agent_assigned_appliance_id;
        $assignedAppliance = $this->agentAssignedApplianceService->getById($assignedApplianceId);
        $agent = $this->agentService->getById($assignedAppliance->agent_id);
        $deviceSerial = $requestData['device_serial'] ?? null;
        $paymentType = $requestData['payment_type'] ?? AppliancePerson::PAYMENT_TYPE_INSTALLMENT;
        $rateType = $requestData['rate_type'] ?? 'monthly';
        $isEnergyService = $paymentType === AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE;

        $downPayment = $requestData['down_payment'] ?? 0;

        // create agent transaction
        $agentTransactionData = [
            'agent_id' => $agent->id,
            'mobile_device_id' => $agent->mobile_device_id,
            'status' => 1,
        ];
        $agentTransaction = $this->agentTransactionService->create($agentTransactionData);

        // assign agent transaction to transaction
        $transactionData = [
            'amount' => $downPayment,
            'sender' => 'Agent-'.$agent->id,
            'message' => $deviceSerial ?? '-',
            'type' => Transaction::TYPE_DOWN_PAYMENT,
        ];

        $transaction = $this->transactionService->make($transactionData);
        $this->agentTransactionTransactionService->setAssignee($agentTransaction);
        $this->agentTransactionTransactionService->setAssigned($transaction);
        $this->agentTransactionTransactionService->assign();
        $this->transactionService->save($transaction);

        // assign agent to appliance person
        $appliancePersonData = [
            'person_id' => $requestData['person_id'],
            'first_payment_date' => $isEnergyService ? null : Carbon::parse($requestData['first_payment_date'])->toDateString(),
            'rate_count' => $isEnergyService ? 0 : $requestData['tenure'],
            'total_cost' => $isEnergyService ? 0 : $assignedAppliance->cost,
            'down_payment' => $downPayment,
            'appliance_id' => $assignedAppliance->appliance->id,
            'device_serial' => $deviceSerial,
            'payment_type' => $paymentType,
            'minimum_payable_amount' => $isEnergyService ? ($requestData['minimum_payable_amount'] ?? null) : null,
            'price_per_day' => $isEnergyService ? ($requestData['price_per_day'] ?? null) : null,
        ];

        $appliancePerson = $this->appliancePersonService->make($appliancePersonData);
        $this->agentAppliancePersonService->setAssignee($agent);
        $this->agentAppliancePersonService->setAssigned($appliancePerson);
        $this->agentAppliancePersonService->assign();
        $this->appliancePersonService->save($appliancePerson);

        if (!$deviceSerial) {
            $transaction->message = (string) $appliancePerson->id;
            $this->transactionService->save($transaction);
        }

        if ($deviceSerial) {
            $device = $this->deviceService->getBySerialNumber($deviceSerial);
            $this->deviceService->update($device, ['person_id' => $requestData['person_id']]);

            $geoJson = empty($requestData['points'])
                ? $appliancePerson->person->addresses()->first()?->geo()->first()?->geo_json
                : GeographicalInformation::pointFromString($requestData['points']);

            $this->deviceService->assignLocation($device, $geoJson);
        }

        // initalize appliance Rates
        $buyer = $this->personService->getById($appliancePerson->person_id);

        if (!$isEnergyService) {
            $this->applianceRateService->create($appliancePerson, $rateType);
        }

        if ($appliancePerson->down_payment > 0) {
            $applianceRate = $this->applianceRateService->createPaidRate($appliancePerson, $appliancePerson->down_payment);
            event(new PaymentSuccessEvent(
                amount: (int) $transaction->amount,
                paymentService: $transaction->original_transaction_type === 'cash_transaction' ? 'web' : 'agent',
                paymentType: Transaction::TYPE_DOWN_PAYMENT,
                sender: $transaction->sender,
                paidFor: $applianceRate,
                payer: $buyer,
                transaction: $transaction,
            ));
        }

        // assign agent assigned appliance to agent balance history
        $agentBalanceHistoryData = [
            'agent_id' => $agent->id,
            'amount' => $downPayment,
            'transaction_id' => $transaction->id,
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
        ];
        $agentBalanceHistory = $this->agentBalanceHistoryService->make($agentBalanceHistoryData);
        $this->agentCommissionHistoryBalanceService->setAssignee($agentCommission);
        $this->agentCommissionHistoryBalanceService->setAssigned($agentBalanceHistory);
        $this->agentCommissionHistoryBalanceService->assign();
        $this->agentBalanceHistoryService->save($agentBalanceHistory);
    }
}
