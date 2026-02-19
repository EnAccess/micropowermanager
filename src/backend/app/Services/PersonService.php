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
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

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
        return QueryBuilder::for($this->person->newQuery())
            ->allowedSorts(['id', 'created_at', 'name'])
            ->defaultSort('-created_at')
            ->get();
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
                'devices' => fn ($q) => $q->whereHas('address')->with('geo'),
            ]
        )->find($personID);
    }

    /**
     * @param string                          $searchTerm could either phone, name or surname
     * @param Request|array<mixed>|int|string $paginate
     *
     * @return Builder<Person>|Collection<int, Person>|LengthAwarePaginator<int, Person>
     */
    public function searchPerson(string $searchTerm, $paginate, int $per_page): Builder|Collection|LengthAwarePaginator {
        $query = $this->person->newQuery()->with(['addresses.city', 'devices'])->whereHas(
            'addresses',
            fn ($q) => $q->where('phone', 'LIKE', $searchTerm.'%')
        )->orWhereHas(
            'devices',
            fn ($q) => $q->where('device_serial', 'LIKE', $searchTerm.'%')
        )->orWhere('name', 'LIKE', $searchTerm.'%')
            ->orWhere('surname', 'LIKE', $searchTerm.'%');

        if ($paginate === 1) {
            return $query->paginate($per_page);
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
            'gender' => $request->get('gender'),
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
    public function getAll(
        ?int $limit = null,
        ?int $customerType = 1,
        ?int $agentId = null,
        ?bool $activeCustomer = null,
        ?int $cityId = null,
        ?float $totalPaidMin = null,
        ?float $totalPaidMax = null,
        ?string $latestPaymentFrom = null,
        ?string $latestPaymentTo = null,
        ?string $registrationFrom = null,
        ?string $registrationTo = null,
        ?string $deviceType = null,
    ): LengthAwarePaginator {
        $query = $this->person->newQuery()
            ->with([
                'addresses.city',
                'devices',
                'agentSoldAppliance',
                'latestPayment',
            ])
            ->where('people.is_customer', $customerType);

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

        if ($cityId) {
            $query->whereHas('addresses', function ($q) use ($cityId) {
                $q->where('city_id', $cityId)
                    ->where('is_primary', 1);
            });
        }

        if ($deviceType) {
            $query->whereHas('devices', function ($q) use ($deviceType) {
                $q->where('device_type', $deviceType);
            });
        }

        if ($latestPaymentFrom) {
            $from = Carbon::parse($latestPaymentFrom);
            $query->whereHas('latestPayment', function ($q) use ($from) {
                $q->where('created_at', '>=', $from);
            });
        }

        if ($latestPaymentTo) {
            $to = Carbon::parse($latestPaymentTo);
            $query->whereHas('latestPayment', function ($q) use ($to) {
                $q->where('created_at', '<=', $to);
            });
        }

        if ($registrationFrom) {
            $from = Carbon::parse($registrationFrom)->startOfDay();
            $query->where('people.created_at', '>=', $from);
        }

        if ($registrationTo) {
            $to = Carbon::parse($registrationTo)->endOfDay();
            $query->where('people.created_at', '<=', $to);
        }

        if (!is_null($totalPaidMin) || !is_null($totalPaidMax)) {
            $query->whereIn('people.id', function ($sub) use ($totalPaidMin, $totalPaidMax) {
                $sub->from('payment_histories')
                    ->select('payer_id')
                    ->groupBy('payer_id');

                if (!is_null($totalPaidMin)) {
                    $sub->havingRaw('SUM(amount) >= ?', [$totalPaidMin]);
                }

                if (!is_null($totalPaidMax)) {
                    $sub->havingRaw('SUM(amount) <= ?', [$totalPaidMax]);
                }
            });
        }

        return QueryBuilder::for($query)
            ->allowedSorts([
                'id',
                'created_at',
                'name',
                AllowedSort::callback('agent', function (Builder $query, bool $descending, string $property) {
                    $direction = $descending ? 'desc' : 'asc';

                    $subquery = DB::table('agent_sold_appliances', 'asa')
                        ->selectRaw('CONCAT(agent_person.name, " ", agent_person.surname)')
                        ->join('agent_assigned_appliances as aaa', 'aaa.id', '=', 'asa.agent_assigned_appliance_id')
                        ->join('agents as ag', 'ag.id', '=', 'aaa.agent_id')
                        ->join('people as agent_person', 'agent_person.id', '=', 'ag.person_id')
                        ->whereColumn('asa.person_id', 'people.id')
                        ->limit(1);

                    $query->orderByRaw('('.$subquery->toSql().') '.$direction)
                        ->addBinding($subquery->getBindings(), 'order');
                }),
                AllowedSort::callback('city', function (Builder $query, bool $descending, string $property) {
                    $direction = $descending ? 'desc' : 'asc';

                    $subquery = DB::table('addresses', 'addr')
                        ->select('c.name')
                        ->join('cities as c', 'c.id', '=', 'addr.city_id')
                        ->whereColumn('addr.owner_id', 'people.id')
                        ->where('addr.owner_type', 'person')
                        ->where('addr.is_primary', 1)
                        ->limit(1);

                    $query->orderByRaw('('.$subquery->toSql().') '.$direction)
                        ->addBinding($subquery->getBindings(), 'order');
                }),
                AllowedSort::callback('device', function (Builder $query, bool $descending, string $property) {
                    $direction = $descending ? 'desc' : 'asc';

                    $subquery = DB::table('devices')
                        ->select('device_serial')
                        ->whereColumn('person_id', 'people.id')
                        ->orderBy('id', 'asc')
                        ->limit(1);

                    $query->orderByRaw('('.$subquery->toSql().') '.$direction)
                        ->addBinding($subquery->getBindings(), 'order');
                }),
            ])
            ->defaultSort('-created_at')
            ->paginate($limit);
    }

    /**
     * @return Collection<int, Person>|array<int, Person>
     */
    public function getAllForExport(?string $miniGridName = null, ?string $villageName = null, ?string $deviceType = null, ?bool $isActive = null): Collection|array {
        $query = $this->person->newQuery()->with([
            'addresses' => fn ($q) => $q->where('is_primary', 1),
            'addresses.city',
            'devices',
        ])->where('is_customer', 1);

        if ($miniGridName) {
            $query->whereHas('addresses', function ($q) use ($miniGridName) {
                $q->whereHas('city', function ($q) use ($miniGridName) {
                    $q->whereHas('miniGrid', function ($q) use ($miniGridName) {
                        $q->where('name', 'LIKE', '%'.$miniGridName.'%');
                    });
                });
            });
        }

        if ($villageName) {
            $query->whereHas('addresses', function ($q) use ($villageName) {
                $q->whereHas('city', function ($q) use ($villageName) {
                    $q->where('name', 'LIKE', '%'.$villageName.'%');
                });
            });
        }

        if ($deviceType) {
            $query->whereHas('devices', function ($q) use ($deviceType) {
                $q->where('device_type', $deviceType);
            });
        }

        if ($isActive === true) {
            $query->whereHas('latestPayment', function ($q) {
                $q->where('created_at', '>=', Carbon::now()->subDays(25));
            });
        } elseif ($isActive === false) {
            $query->whereDoesntHave('latestPayment', function ($q) {
                $q->where('created_at', '>=', Carbon::now()->subDays(25));
            });
        }

        return QueryBuilder::for($query)
            ->allowedSorts(['id', 'created_at', 'name'])
            ->defaultSort('-created_at')
            ->get();
    }

    public function createFromRequest(Request $request): Person {
        $person = $this->person->newQuery()->create($request->only([
            'title',
            'education',
            'name',
            'surname',
            'birth_date',
            'gender',
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
