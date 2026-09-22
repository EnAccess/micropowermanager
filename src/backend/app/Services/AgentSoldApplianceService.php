<?php

namespace App\Services;

use App\Enums\PaymentInitiationProvider;
use App\Models\Agent;
use App\Models\AgentAssignedAppliances;
use App\Models\AgentSoldAppliance;
use App\Models\AppliancePerson;
use App\Models\GeographicalInformation;
use App\Models\Person\Person;
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
        private AgentAssignedApplianceService $agentAssignedApplianceService,
        private AgentBalanceHistoryService $agentBalanceHistoryService,
        private AgentCommissionService $agentCommissionService,
        private AgentService $agentService,
        private AgentSoldAppliance $agentSoldAppliance,
        private AgentTransactionService $agentTransactionService,
        private PaymentInitiationService $paymentInitiationService,
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
     * The sales an agent made, as AppliancePerson records: their ids address the sold
     * appliance detail endpoint and their cost is what the customer was charged.
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
     * A sold appliance the agent may collect a payment for: any in their mini-grid, including one
     * sold by an admin or by another agent, since collection is a field visit rather than a claim
     * on the original sale.
     */
    public function findForAgent(Agent $agent, int $appliancePersonId): AppliancePerson {
        return $this->appliancePerson->newQuery()
            ->whereHas(
                'person.addresses',
                fn ($q) => $q->where('is_primary', 1)
                    ->whereHas('city', fn ($q) => $q->where('mini_grid_id', $agent->mini_grid_id))
            )
            ->findOrFail($appliancePersonId);
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
     *
     * @return array{sold_appliance: AgentSoldAppliance, transaction: Transaction|null, provider_data: array<string, mixed>, process_immediately: bool}
     */
    public function sell(array $requestData): array {
        $agentSoldAppliance = $this->create([
            'person_id' => $requestData['person_id'],
            'agent_assigned_appliance_id' => $requestData['agent_assigned_appliance_id'],
        ]);

        return ['sold_appliance' => $agentSoldAppliance]
            + $this->processSaleFromRequest($agentSoldAppliance, $requestData);
    }

    /**
     * Records the sale and starts its down payment. The down payment is an outstanding rate due on
     * the day of the sale, collected through the transaction pipeline so that it issues a token
     * like any other payment against the appliance does.
     *
     * The provider charge is the last thing this does, so a rejection unwinds the whole sale when
     * the caller rolls back. Returns a null transaction when there is no down payment to collect.
     *
     * @param array<string, mixed> $requestData
     *
     * @return array{transaction: Transaction|null, provider_data: array<string, mixed>, process_immediately: bool}
     */
    public function processSaleFromRequest(AgentSoldAppliance $agentSoldAppliance, array $requestData = []): array {
        $assignedApplianceId = $agentSoldAppliance->agent_assigned_appliance_id;
        $assignedAppliance = $this->agentAssignedApplianceService->getById($assignedApplianceId);
        $agent = $this->agentService->getById($assignedAppliance->agent_id);
        $deviceSerial = $requestData['device_serial'] ?? null;
        $paymentType = $requestData['payment_type'] ?? AppliancePerson::PAYMENT_TYPE_INSTALLMENT;
        $rateType = $requestData['rate_type'] ?? 'monthly';
        $isEnergyService = $paymentType === AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE;

        $downPayment = $requestData['down_payment'] ?? 0;
        $collectsDownPayment = $downPayment > 0;

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

        if ($deviceSerial) {
            $device = $this->deviceService->getBySerialNumber($deviceSerial);
            $this->deviceService->update($device, ['person_id' => $requestData['person_id']]);

            $geoJson = empty($requestData['points'])
                ? $appliancePerson->person->addresses()->first()?->geo()->first()?->geo_json
                : GeographicalInformation::pointFromString($requestData['points']);

            $this->deviceService->assignLocation($device, $geoJson);
        }

        if (!$isEnergyService) {
            $this->applianceRateService->create($appliancePerson, $rateType);

            if ($collectsDownPayment) {
                $this->applianceRateService->createDownPaymentRate($appliancePerson);
            }
        }

        // The routing key TransactionPaymentProcessor resolves the payment against.
        $message = $deviceSerial ?? (string) $appliancePerson->id;
        $providerId = (int) ($requestData['payment_provider'] ?? PaymentInitiationProvider::Cash->value);

        if ($providerId === PaymentInitiationProvider::Cash->value) {
            return $this->recordCashDownPayment($agent, $assignedAppliance, (float) $downPayment, $message);
        }

        if (!$collectsDownPayment) {
            return ['transaction' => null, 'provider_data' => [], 'process_immediately' => false];
        }

        return $this->initiateProviderDownPayment(
            $agent,
            $this->personService->getById($appliancePerson->person_id),
            (float) $downPayment,
            $message,
            $deviceSerial,
            $providerId,
            $requestData['payer_phone'] ?? null,
        );
    }

    /**
     * The agent took the money, so their balance and commission are credited here rather than off a
     * settlement event. The customer's side of the payment — the down payment rate, the payment
     * history and the token — is left to the transaction pipeline, which is why the caller is asked
     * to process the transaction immediately.
     *
     * A sale that collected nothing is recorded but earns neither commission nor a transaction:
     * the commission follows the deposit, and a transaction of nothing can never be processed yet
     * would still surface as a payment in transaction lists and exports.
     *
     * @return array{transaction: Transaction|null, provider_data: array<string, mixed>, process_immediately: bool}
     */
    private function recordCashDownPayment(
        Agent $agent,
        AgentAssignedAppliances $assignedAppliance,
        float $downPayment,
        string $message,
    ): array {
        if ($downPayment <= 0) {
            return ['transaction' => null, 'provider_data' => [], 'process_immediately' => false];
        }

        $agentTransaction = $this->agentTransactionService->create([
            'agent_id' => $agent->id,
            'mobile_device_id' => $agent->mobile_device_id,
            'status' => 1,
        ]);

        $transaction = $this->transactionService->make([
            'amount' => $downPayment,
            'sender' => 'Agent-'.$agent->id,
            'message' => $message,
            'type' => Transaction::TYPE_DOWN_PAYMENT,
        ]);
        $transaction->originalTransaction()->associate($agentTransaction);
        $this->transactionService->save($transaction);

        $this->agentBalanceHistoryService->creditBalance(
            $agent,
            $transaction,
            $downPayment,
            $assignedAppliance,
        );

        $agentCommission = $this->agentCommissionService->getById($agent->agent_commission_id);

        $this->agentBalanceHistoryService->creditCommission(
            $agent,
            $transaction,
            $assignedAppliance->cost * $agentCommission->appliance_commission,
        );

        return ['transaction' => $transaction, 'provider_data' => [], 'process_immediately' => true];
    }

    /**
     * The customer pays the provider directly, so nothing is settled here: no paid rate, no
     * payment history and no ledger rows. Those follow once the payment is confirmed and the
     * transaction is processed, which is also when the agent earns their commission.
     *
     * @return array{transaction: Transaction, provider_data: array<string, mixed>, process_immediately: bool}
     */
    private function initiateProviderDownPayment(
        Agent $agent,
        Person $buyer,
        float $downPayment,
        string $message,
        ?string $deviceSerial,
        int $providerId,
        ?string $payerPhoneOverride,
    ): array {
        $payerPhone = $payerPhoneOverride ?? $this->personService->getPrimaryPhoneNumber($buyer);

        $result = $this->paymentInitiationService->initiate(
            providerId: $providerId,
            amount: $downPayment,
            sender: $payerPhone ?? '-',
            message: $message,
            type: Transaction::TYPE_DOWN_PAYMENT,
            customerId: $buyer->id,
            serialId: $deviceSerial,
        );

        $result['transaction']->agent_id = $agent->id;
        $result['transaction']->save();

        return $result;
    }
}
