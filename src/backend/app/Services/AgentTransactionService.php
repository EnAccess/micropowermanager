<?php

namespace App\Services;

use App\Exceptions\Device\DeviceIsNotAssignedToCustomer;
use App\Models\Agent;
use App\Models\Device;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\Transaction;
use App\Services\Interfaces\IAgentTransactionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class AgentTransactionService implements IAgentTransactionService {
    public function __construct(
        private AgentTransaction $agentTransaction,
        private Transaction $transaction,
        private Device $device,
        private DeviceService $deviceService,
        private PaymentInitiationService $paymentInitiationService,
        private PersonService $personService,
    ) {}

    /**
     * Starts a meter top-up the agent collects through a payment provider instead of in cash. The
     * provider pushes the request to the customer's own phone, so the payer is the device owner
     * rather than the agent, and `agent_id` is what keeps the payment attributable to the agent.
     *
     * The caller dispatches ProcessPayment after committing: the provider charge is the last thing
     * that happens here, so nothing reversible may follow it.
     *
     * @return array{transaction: Transaction, provider_data: array<string, mixed>, process_immediately: bool}
     */
    public function initiateProviderPayment(
        Agent $agent,
        string $deviceSerial,
        float $amount,
        int $providerId,
        ?string $payerPhoneOverride = null,
    ): array {
        $customer = $this->deviceService->getBySerialNumber($deviceSerial)?->person;

        if ($customer === null) {
            throw new DeviceIsNotAssignedToCustomer("Device {$deviceSerial} is not assigned to a customer.");
        }

        $payerPhone = $payerPhoneOverride ?? $this->personService->getPrimaryPhoneNumber($customer);

        $result = $this->paymentInitiationService->initiate(
            providerId: $providerId,
            amount: $amount,
            sender: $payerPhone ?? '-',
            message: $deviceSerial,
            type: Transaction::TYPE_ENERGY,
            customerId: $customer->id,
            serialId: $deviceSerial,
        );

        $result['transaction']->agent_id = $agent->id;
        $result['transaction']->save();

        return $result;
    }

    /**
     * @return Collection<int, Transaction>|LengthAwarePaginator<int, Transaction>
     */
    public function getAll(
        ?int $limit = null,
        ?int $agentId = null,
        bool $forApp = false,
    ): Collection|LengthAwarePaginator {
        $query = $this->transaction->newQuery();

        if ($forApp) {
            $query->with([
                'originalTransaction',
                'device' => fn ($q) => $q->whereHas('person')->with(['device', 'person']),
                'nonPaygoAppliance.person',
                'nonPaygoAppliance.appliance',
            ]);
        } else {
            $query->with(['device' => fn ($q) => $q->whereHas('person')->with(['device', 'person'])]);
        }

        $query->where(fn (Builder $subQuery) => $subQuery
            ->where('agent_id', $agentId)
            ->orWhereHasMorph(
                'originalTransaction',
                [AgentTransaction::class],
                static function ($q) use ($agentId) {
                    $q->where('agent_id', $agentId);
                }
            ))->latest()->orderByDesc('id');

        return $limit ? $query->paginate($limit) : $query->get();
    }

    /**
     * @return Collection<int, Transaction>|LengthAwarePaginator<int, Transaction>
     */
    public function getByCustomerId(int $agentId, ?int $customerId = null): Collection|LengthAwarePaginator {
        $customerDeviceSerials = $this->device->newQuery()->where('person_id', $customerId)
            ->pluck('device_serial');

        if (!$customerDeviceSerials->count()) {
            return new Collection();
        }

        return $this->transaction->newQuery()
            ->with(['originalTransaction', 'device' => fn ($q) => $q->whereHas('person')->with(['device', 'person'])])
            ->where(fn (Builder $subQuery) => $subQuery
                ->where('agent_id', $agentId)
                ->orWhereHasMorph(
                    'originalTransaction',
                    [AgentTransaction::class],
                    fn ($q) => $q->where('agent_id', $agentId)
                ))
            ->whereHas('device', fn ($q) => $q->whereIn('device_serial', $customerDeviceSerials))
            ->latest()
            ->orderByDesc('id')
            ->paginate();
    }

    public function getById(int $id): AgentTransaction {
        throw new \Exception('Method getById() not yet implemented.');
    }

    /**
     * Find a transaction the given agent may read the token of, and eager-load
     * that token. A transaction qualifies when the agent recorded it or initiated it
     * through a payment provider, or when it
     * targeted a device of a customer in the agent's mini-grid — an installment
     * settled by an admin, by another agent or by a payment provider still has
     * to show its token in the field app. Returns null when the transaction
     * doesn't exist or falls outside both.
     */
    public function findForAgent(Agent $agent, int $transactionId): ?Transaction {
        return $this->transaction->newQuery()
            ->with(['token'])
            ->where('id', $transactionId)
            ->where(fn (Builder $query) => $query->where('agent_id', $agent->id)->orWhereHasMorph(
                'originalTransaction',
                [AgentTransaction::class],
                fn ($q) => $q->where('agent_id', $agent->id)
            )->orWhereHas(
                'device.person.addresses.city',
                fn ($q) => $q->where('mini_grid_id', $agent->mini_grid_id)
            ))
            ->first();
    }

    /**
     * @param array<string, mixed> $transactionData
     */
    public function create(array $transactionData): AgentTransaction {
        return $this->agentTransaction->newQuery()->create($transactionData);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function update(AgentTransaction $model, array $data): AgentTransaction {
        throw new \Exception('Method update() not yet implemented.');
    }

    public function delete(AgentTransaction $model): ?bool {
        throw new \Exception('Method delete() not yet implemented.');
    }
}
