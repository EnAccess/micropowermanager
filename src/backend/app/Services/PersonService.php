<?php

namespace App\Services;

use App\DTO\PersonListFilters;
use App\Models\Country;
use App\Models\Person\Person;
use App\Services\Interfaces\IBaseService;
use App\Traits\HasCrudOperations;
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
    /** @use HasCrudOperations<Person> */
    use HasCrudOperations;

    public function __construct(
        private Person $person,
    ) {}

    protected function crudModel(): Person {
        return $this->person;
    }

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
                'devices' => fn ($q) => $q->with('geo'),
            ]
        )->find($personID);
    }

    /**
     * @return Builder<Person>|Collection<int, Person>|LengthAwarePaginator<int, Person>
     */
    public function searchPerson(string $searchTerm, int $paginate, int $perPage): Builder|Collection|LengthAwarePaginator {
        // Phone numbers are stored in E.164 format (with a leading `+`), but the
        // term usually arrives without one: query strings decode a literal `+` as
        // a space (form-encoding rules) and the TrimStrings middleware strips it,
        // unless the client encodes it as `%2B`.
        // Match the term both with and without the leading `+`, keeping every pattern prefix-anchored so the
        // index on `addresses.phone` stays usable.
        $phonePrefixes = array_unique([$searchTerm, '+'.ltrim($searchTerm, '+')]);

        $query = $this->person->newQuery()->with(['addresses.city', 'devices'])->whereHas(
            'addresses',
            fn ($q) => $q->where(function ($phoneQuery) use ($phonePrefixes) {
                foreach ($phonePrefixes as $phonePrefix) {
                    $phoneQuery->orWhere('phone', 'LIKE', $phonePrefix.'%');
                }
            })
        )->orWhereHas(
            'devices',
            fn ($q) => $q->where('device_serial', 'LIKE', $searchTerm.'%')
        )->orWhere('name', 'LIKE', $searchTerm.'%')
            ->orWhere('surname', 'LIKE', $searchTerm.'%');

        if ($paginate === 1) {
            return $query->paginate($perPage);
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
            'title' => $request->input('title'),
            'education' => $request->input('education'),
            'name' => $request->input('name'),
            'surname' => $request->input('surname'),
            'birth_date' => $request->input('birth_date'),
            'gender' => $request->input('gender'),
            'is_customer' => $request->input('is_customer') ?? 0,
            'mini_grid_id' => $request->input('mini_grid_id'),
        ];
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

    /**
     * @return LengthAwarePaginator<int, Person>
     */
    public function getAll(?int $perPage = null, PersonListFilters $filters = new PersonListFilters()): LengthAwarePaginator {
        $query = $this->person->newQuery()
            ->with([
                'addresses.city',
                'devices',
                'agentSoldAppliance',
                'latestPayment',
            ])
            ->where('people.is_customer', $filters->isCustomer);

        if ($filters->agentId) {
            $query->whereHas('agentSoldAppliance.assignedAppliance.agent', function ($q) use ($filters) {
                $q->where('id', $filters->agentId);
            });
        }

        if (!is_null($filters->activeCustomer)) {
            // For active customers (true), show those with recent payments
            if ($filters->activeCustomer) {
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

        if ($filters->cityId) {
            $query->whereHas('addresses', function ($q) use ($filters) {
                $q->where('city_id', $filters->cityId)
                    ->where('is_primary', 1);
            });
        }

        if ($filters->deviceType) {
            $query->whereHas('devices', function ($q) use ($filters) {
                $q->where('device_type', $filters->deviceType);
            });
        }

        if ($filters->latestPaymentFrom) {
            $from = Carbon::parse($filters->latestPaymentFrom);
            $query->whereHas('latestPayment', function ($q) use ($from) {
                $q->where('created_at', '>=', $from);
            });
        }

        if ($filters->latestPaymentTo) {
            $to = Carbon::parse($filters->latestPaymentTo);
            $query->whereHas('latestPayment', function ($q) use ($to) {
                $q->where('created_at', '<=', $to);
            });
        }

        if ($filters->registrationFrom) {
            $from = Carbon::parse($filters->registrationFrom)->startOfDay();
            $query->where('people.created_at', '>=', $from);
        }

        if ($filters->registrationTo) {
            $to = Carbon::parse($filters->registrationTo)->endOfDay();
            $query->where('people.created_at', '<=', $to);
        }

        if (!is_null($filters->totalPaidMin) || !is_null($filters->totalPaidMax)) {
            $query->whereIn('people.id', function ($sub) use ($filters) {
                $sub->from('payment_histories')
                    ->select('payer_id')
                    ->groupBy('payer_id');

                if (!is_null($filters->totalPaidMin)) {
                    $sub->havingRaw('SUM(amount) >= ?', [$filters->totalPaidMin]);
                }

                if (!is_null($filters->totalPaidMax)) {
                    $sub->havingRaw('SUM(amount) <= ?', [$filters->totalPaidMax]);
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
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, Person>|array<int, Person>
     */
    public function getAllForExport(?string $miniGridName = null, ?string $villageName = null, ?string $deviceType = null, ?bool $isActive = null): Collection|array {
        $query = $this->person->newQuery()->with([
            'addresses' => fn ($q) => $q->where('is_primary', 1),
            'addresses.city',
            'addresses.geo',
            'devices',
            'latestPayment',
            'agentSoldAppliance.assignedAppliance.agent.person',
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
            'city_id' => $request->input('city_id') ?? 1,
            'email' => $request->input('email') ?? '',
            'phone' => $request->input('phone') ?? '',
            'street' => $request->input('street') ?? '',
            'is_primary' => 1,
        ];

        $address = $addressService->instantiate($addressParams);
        $addressService->assignAddressToOwner($person, $address);

        return $person;
    }

    public function getByPhoneNumber(string $phoneNumber): ?Person {
        $phoneNumber = phone($phoneNumber)->formatE164();

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
