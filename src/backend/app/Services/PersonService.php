<?php

namespace App\Services;

use App\Models\Country;
use App\Models\Person\Person;
use App\Services\Interfaces\IBaseService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

/**
 * @implements IBaseService<Person>
 */
class PersonService implements IBaseService {
    public function __construct(
        private Person $person,
    ) {}

    /**
     * @return Collection<int, Person>|array<int, Person>
     */
    public function getAllRegisteredPeople(): Collection|array {
        return $this->person->newQuery()->get();
    }

    // associates the person with a country
    public function addCitizenship(Person $person, Country $country): Person {
        return $person->citizenship()->associate($country);
    }

    public function getDetails(int $personID, bool $allRelations = false): ?Person {
        if (!$allRelations) {
            return $this->getById($personID);
        }

        return $this->person->newQuery()->with(
            [
                'addresses' => fn ($q) => $q->orderBy('is_primary')
                    ->with('city', fn ($q) => $q->whereHas('location'))
                    ->with('city.location')
                    ->with('geo')
                    ->get(),
                'citizenship',
                'roleOwner.definitions',
                'devices' => fn ($q) => $q->whereHas('address')->with('address.geo'),
            ]
        )->find($personID);
    }

    /**
     * @param string                          $searchTerm could either phone, name or surname
     * @param Request|array<mixed>|int|string $paginate
     *
     * @return Builder<Person>|Collection<int, Person>|LengthAwarePaginator<int, Person>
     */
    public function searchPerson(string $searchTerm, $paginate): Builder|Collection|LengthAwarePaginator {
        $query = $this->person->newQuery()->with(['addresses.city', 'devices'])->whereHas(
            'addresses',
            fn ($q) => $q->where('phone', 'LIKE', '%'.$searchTerm.'%')
        )->orWhereHas(
            'devices',
            fn ($q) => $q->where('device_serial', 'LIKE', '%'.$searchTerm.'%')
        )->orWhere('name', 'LIKE', '%'.$searchTerm.'%')
            ->orWhere('surname', 'LIKE', '%'.$searchTerm.'%');

        if ($paginate === 1) {
            return $query->paginate(15);
        }

        return $query->get();
    }

    /**
     * @return LengthAwarePaginator<int, mixed>
     */
    public function getPersonTransactions(Person $person): LengthAwarePaginator {
        return $person->payments()->with('transaction.token')->latest()->paginate(7);
    }

    /**
     * @param array<string, mixed> $personData
     */
    public function createMaintenancePerson(array $personData): Person {
        $personData['is_customer'] = 0;
        $personData['type'] = 'maintenance';

        return $this->person->newQuery()->create($personData);
    }

    public function livingInCluster(int $clusterId): \Illuminate\Database\Query\Builder {
        return $this->person->livingInClusterQuery($clusterId);
    }

    /**
     * @param array<int> $peopleId
     *
     * @return Builder<Person>
     */
    public function getBulkDetails(array $peopleId): Builder {
        return $this->person->newQuery()->with(
            [
                'addresses' => fn ($q) => $q->where('is_primary', '=', 1),
                'addresses.city',
                'citizenship',
                'roleOwner.definitions',
                'devices.device',
                'devices.device.tariff',
            ]
        )->whereIn('id', $peopleId);
    }

    public function updatePersonUpdatedDate(Person $person): void {
        $person->updated_at = Carbon::now();
        $person->save();
    }

    public function isMaintenancePerson(?string $customerType): bool {
        return $customerType !== null && $customerType !== 'customer' && $customerType === 'maintenance';
    }

    /**
     * @return array<string, mixed>
     */
    public function createPersonDataFromRequest(Request $request): array {
        return [
            'title' => $request->get('title'),
            'education' => $request->get('education'),
            'name' => $request->get('name'),
            'surname' => $request->get('surname'),
            'birth_date' => $request->get('birth_date'),
            'sex' => $request->get('sex'),
            'is_customer' => $request->get('is_customer') ?? 0,
            'mini_grid_id' => $request->get('mini_grid_id'),
        ];
    }

    public function getById(int $personId): Person {
        return $this->person->newQuery()->find($personId);
    }

    /**
     * @param array<string, mixed> $personData
     */
    public function create(array $personData): Person {
        return $this->person->newQuery()->create($personData);
    }

    /**
     * @param array<string, mixed> $personData
     */
    public function update($person, array $personData): Person {
        foreach ($personData as $key => $value) {
            $person->$key = $value;
        }

        $person->save();
        $person->fresh();

        return $person;
    }

    public function delete($person): ?bool {
        return $person->delete();
    }

    /**
     * @return LengthAwarePaginator<int, Person>
     */
    public function getAll(?int $limit = null, ?int $customerType = 1, ?int $agentId = null, ?bool $activeCustomer = null): LengthAwarePaginator {
        $query = $this->person->newQuery()->with([
            'addresses' => fn ($q) => $q->where('is_primary', 1),
            'addresses.city',
            'devices',
            'agentSoldAppliance.assignedAppliance.agent',
            'latestPayment',
        ])->where('is_customer', $customerType);

        if ($agentId) {
            $query->whereHas('agentSoldAppliance.assignedAppliance.agent', function ($q) use ($agentId) {
                $q->where('id', $agentId);
            });
        }

        if (!is_null($activeCustomer)) {
            // For active customers (true), show those with recent payments
            if ($activeCustomer) {
                $query->whereHas('payments', function ($q) {
                    $q->where('created_at', '>=', Carbon::now()->subDays(25));
                });
            } else {
                // For inactive customers (false), exclude those with recent payments
                $query->whereDoesntHave('payments', function ($q) {
                    $q->where('created_at', '>=', Carbon::now()->subDays(25));
                });
            }
        }

        return $query->paginate($limit);
    }

    /**
     * @return Collection<int, Person>|array<int, Person>
     */
    public function getAllForExport(?int $miniGrid = null, ?int $village = null, ?string $deviceType = null, ?bool $isActive = null, ?string $status = null): Collection|array {
        $isActive = $isActive == null ? 1 : $isActive;
        $query = $this->person->newQuery()->with([
            'addresses' => fn ($q) => $q->where('is_primary', 1),
            'addresses.city',
            'devices',
        ])->where('is_customer', $isActive);

        if ($status) {
            $query->where('status', $status);
        }

        if ($miniGrid) {
            $query->whereHas('addresses', function ($q) use ($miniGrid) {
                $q->whereHas('city', function ($q) use ($miniGrid) {
                    $q->where('mini_grid_id', $miniGrid);
                });
            });
        }

        if ($village) {
            $query->whereHas('addresses', function ($q) use ($village) {
                $q->where('city_id', $village);
            });
        }

        if ($deviceType) {
            $query->whereHas('devices', function ($q) use ($deviceType) {
                $q->where('device_type', $deviceType);
            });
        }

        return $query->get();
    }

    public function createFromRequest(Request $request): Person {
        $person = $this->person->newQuery()->create($request->only([
            'title',
            'education',
            'name',
            'surname',
            'birth_date',
            'sex',
            'is_customer',
        ]));

        $addressService = app()->make(AddressesService::class);
        $addressParams = [
            'city_id' => $request->get('city_id') ?? 1,
            'email' => $request->get('email') ?? '',
            'phone' => $request->get('phone') ?? '',
            'street' => $request->get('street') ?? '',
            'is_primary' => 1,
        ];

        $address = $addressService->instantiate($addressParams);
        $addressService->assignAddressToOwner($person, $address);

        return $person;
    }

    public function getByPhoneNumber(string $phoneNumber): ?Person {
        return $this->person->newQuery()->whereHas('addresses', fn ($q) => $q->where('phone', $phoneNumber))
            ->first();
    }

    /**
     * @return Collection<int, Person>|array<int, Person>
     */
    public function getAllMaintenanceUsers(): Collection|array {
        return $this->person->newQuery()->where('type', 'maintenance')->get();
    }
}
