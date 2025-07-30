<?php

namespace App\Services;

use App\Helpers\PasswordGenerator;
use App\Models\Agent;
use App\Services\Interfaces\IBaseService;
use Complex\Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * @implements IBaseService<Agent>
 */
class AgentService implements IBaseService {
    public function __construct(
        private Agent $agent,
        private PersonService $personService,
    ) {}

    public function resetPassword(string $email): string {
        try {
            $newPassword = PasswordGenerator::generatePassword();
        } catch (Exception $exception) {
            $newPassword = (string) time();
        }

        try {
            $agent = $this->agent->newQuery()->where('email', $email)->firstOrFail();
        } catch (ModelNotFoundException $x) {
            $message = 'Invalid email.';

            return $message;
        }

        $agent->password = $newPassword;
        $agent->update();
        $agent->fresh();

        return $newPassword;
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
     * @return Collection<int, Agent>|LengthAwarePaginator<Agent>
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

    public function delete($agent): ?bool {
        return $agent->delete();
    }

    /**
     * @return Collection<int, Agent>|LengthAwarePaginator<Agent>
     */
    public function getAll(?int $limit = null): Collection|LengthAwarePaginator {
        if ($limit) {
            return $this->agent->newQuery()
                ->with(['person', 'person.addresses', 'miniGrid', 'commission'])
                ->paginate($limit);
        }

        return $this->agent->newQuery()
            ->with(['person.addresses', 'miniGrid'])
            ->get();
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
        $person = $this->personService->getById($agentData['personId']);
        $personData = [
            'name' => $agentData['name'],
            'surname' => $agentData['surname'],
            'sex' => $agentData['gender'],
            'birth_date' => $agentData['birthday'],
        ];
        $person = $this->personService->update($person, $personData);
        $address = $person->addresses()->where('is_primary', 1)->first();
        $address->phone = $agentData['phone'];
        $address->update();
        $agent->person->name = $agentData['name'];
        $agent->agent_commission_id = $agentData['commissionTypeId'];
        $agent->update();

        return $this->agent->with(['person', 'person.addresses', 'miniGrid', 'commission'])
            ->where('id', $agent->id)->first();
    }
}
