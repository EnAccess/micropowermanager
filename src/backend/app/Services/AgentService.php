<?php

namespace App\Services;

use App\Helpers\PasswordGenerator;
use App\Models\Agent;
use App\Models\AppliancePerson;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
use Complex\Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<Agent>
 */
class AgentService implements IBaseService {
    /** @use HasCrudOperations<Agent> */
    use HasCrudOperations;

    /**
     * An agent counts as active when it has recorded balance history (a payment
     * or sale) within this many days.
     */
    public const ACTIVE_WINDOW_DAYS = 30;

    public function __construct(
        private Agent $agent,
        private PersonService $personService,
    ) {}

    protected function crudModel(): Agent {
        return $this->agent;
    }

    public function resetPassword(string $email): string {
        try {
            $newPassword = PasswordGenerator::generatePassword();
        } catch (Exception) {
            $newPassword = (string) time();
        }

        try {
            $agent = $this->agent->newQuery()->where('email', $email)->firstOrFail();
        } catch (ModelNotFoundException) {
            return 'Invalid email.';
        }

        $agent->password = $newPassword;
        $agent->update();
        $agent->fresh();

        return $newPassword;
    }

    public function changePassword(Agent $agent, #[\SensitiveParameter] string $password): Agent {
        $agent->password = $password;
        $agent->save();

        return $agent->fresh();
    }

    public function updateDevice(Agent $agent, string $deviceId): void {
        $agent->mobile_device_id = $deviceId;
        $agent->update();
        $agent->fresh();
    }

    public function setFirebaseToken(Agent $agent, ?string $firebaseToken): ?Agent {
        $agent->fire_base_token = $firebaseToken;
        $agent->update();

        return $agent->fresh();
    }

    public function getAgentBalance(Agent $agent): float {
        return $agent->balance;
    }

    /**
     * @return Collection<int, Agent>|LengthAwarePaginator<int, Agent>
     */
    public function searchAgent(string $searchTerm, int $paginate): Collection|LengthAwarePaginator {
        if ($paginate === 1) {
            return $this->agent->newQuery()->with(['miniGrid', 'person'])->WhereHas(
                'miniGrid',
                function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', '%'.$searchTerm.'%');
                }
            )->orWhereHas('person', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%'.$searchTerm.'%');
            })->orWhere('email', 'LIKE', '%'.$searchTerm.'%')->paginate(15);
        }

        return $this->agent->newQuery()->with('miniGrid')->WhereHas(
            'miniGrid',
            function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%'.$searchTerm.'%');
            }
        )->orWhereHas('person', function ($q) use ($searchTerm) {
            $q->where('name', 'LIKE', '%'.$searchTerm.'%');
        })->orWhere('name', 'LIKE', '%'.$searchTerm.'%')
            ->orWhere('email', 'LIKE', '%'.$searchTerm.'%')->get();
    }

    public function getByAuthenticatedUser(): ?Agent {
        return $this->agent->newQuery()->find(auth('agent_api')->user()->id);
    }

    public function getById(int $id): Agent {
        return $this->agent->newQuery()
            ->with(['person', 'person.addresses', 'miniGrid', 'commission'])
            ->where('id', $id)->firstOrFail();
    }

    /**
     * @return Collection<int, Agent>|LengthAwarePaginator<int, Agent>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        $query = $this->agent->newQuery()
            ->select('agents.*')
            ->addSelect(['customer_count' => AppliancePerson::query()
                ->selectRaw('COUNT(DISTINCT person_id)')
                ->whereColumn('creator_id', 'agents.id')
                ->where('creator_type', $this->agent->getMorphClass())])
            ->with(['person.addresses', 'miniGrid', 'commission'])
            ->withCount(['soldAppliances AS sales_count'])
            ->withExists(['balanceHistory AS is_active' => fn (Builder $query) => $query->where('created_at', '>=', now()->subDays(self::ACTIVE_WINDOW_DAYS))]);

        if ($limit) {
            return $query->paginate($limit);
        }

        return $query->get();
    }

    /**
     * @param array<string, mixed>      $agentData
     * @param array<string, mixed>|null $addressData
     * @param array<string, mixed>|null $personData
     */
    public function create(
        array $agentData,
        ?array $addressData = null,
        ?array $personData = null,
        ?object $country = null,
        ?object $addressService = null,
        ?object $countryService = null,
        ?object $personService = null,
        ?object $personAddressService = null,
    ): Agent {
        // Ensure the person is created with type 'agent'
        $personData['type'] = 'agent';
        $person = $personService->create($personData);

        if ($country !== null) {
            $person = $personService->addCitizenship($person, $country);
        }

        $agentData['person_id'] = $person->id;
        $address = $addressService->make($addressData);
        $personAddressService->setAssignee($person);
        $personAddressService->setAssigned($address);
        $personAddressService->assign();
        $addressService->save($address);

        return $this->agent->newQuery()->create($agentData);
    }

    /**
     * @param array<string, mixed> $agentData
     */
    public function update($agent, array $agentData): Agent {
        $personData = array_filter([
            'name' => $agentData['name'] ?? null,
            'surname' => $agentData['surname'] ?? null,
            'gender' => $agentData['gender'] ?? null,
            'birth_date' => $agentData['birthday'] ?? null,
        ], fn ($value): bool => $value !== null);

        if ($personData !== []) {
            $this->personService->update($agent->person, $personData);
        }

        if (array_key_exists('phone', $agentData)) {
            $address = $agent->person->addresses()->where('is_primary', 1)->first();
            if ($address !== null) {
                $address->phone = $agentData['phone'];
                $address->update();
            }
        }

        if (array_key_exists('commissionTypeId', $agentData)) {
            $agent->agent_commission_id = $agentData['commissionTypeId'];
        }
        if (array_key_exists('miniGridId', $agentData)) {
            $agent->mini_grid_id = $agentData['miniGridId'];
        }
        if ($agent->isDirty()) {
            $agent->update();
        }

        return $this->agent->with(['person', 'person.addresses', 'miniGrid', 'commission'])
            ->where('id', $agent->id)->first();
    }
}
