<?php

namespace App\Services;

use App;
use App\Models\Address\Address;
use App\Models\MaintenanceUsers;
use App\Models\Person\Person;
use App\Models\Country;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class PersonService implements IBaseService
{
    public function __construct(private Person $person)
    {
    }


    public function getAllRegisteredPeople(): Collection|array
    {
        return $this->person->newQuery()->get();
    }

    // associates the person with a country
    public function addCitizenship(Person $person, Country $country): Model
    {
        return $person->citizenship()->associate($country);
    }

    public function getDetails(int $personID, bool $allRelations = false)
    {
        if (!$allRelations) {
            return $this->getById($personID);
        }

        return $this->person->newQuery()->with(
            [
                'addresses' => fn($q) => $q->orderBy('is_primary')->with('city', fn($q) => $q->whereHas('location'))
                    ->get(),
                'citizenship',
                'roleOwner.definitions',
                'devices' => fn($q) => $q->whereHas('address')->with('address.geo'),
            ]
        )->find($personID);
    }

    /**
     * @param string $searchTerm could either phone, name or surname
     * @param Request|array|int|string $paginate
     *
     * @return Builder[]|Collection|LengthAwarePaginator
     *
     * @psalm-return Collection|LengthAwarePaginator|array<array-key, Builder>
     */
    public function searchPerson($searchTerm, $paginate)
    {
        $query = $this->person->newQuery()->with(['addresses.city', 'devices'])->whereHas(
            'addresses', fn($q) => $q->where('phone', 'LIKE', '%' . $searchTerm . '%')
        )->orWhereHas(
            'devices',
            fn($q) => $q->where('device_serial', 'LIKE', '%' . $searchTerm . '%')
        )->orWhere('name', 'LIKE', '%' . $searchTerm . '%')
            ->orWhere('surname', 'LIKE', '%' . $searchTerm . '%');

        if ($paginate === 1) {
            return $query->paginate(15);
        }

        return $query->get();
    }

    public function getPersonTransactions($person)
    {
        return $person->payments()->with('transaction.token')->latest()->paginate(7);
    }

    public function createMaintenancePerson(array $personData): Person
    {
        $personData['is_customer'] = 0;
        /** @var Person $person */
        $person = $this->person->newQuery()->create($personData);
        return $person;
    }

    public function livingInCluster(int $clusterId)
    {
        return $this->person->livingInClusterQuery($clusterId);
    }

    public function getBulkDetails(array $peopleId): Builder
    {
        return $this->person->newQuery()->with(
            [
                'addresses' => fn($q) => $q->where('is_primary', '=', 1),
                'addresses.city',
                'citizenship',
                'roleOwner.definitions',
                'meters.meter',
                'meters.tariff',
            ]
        )->whereIn('id', $peopleId);
    }

    public function updatePersonUpdatedDate(Person $person)
    {
        $person->updated_at = Carbon::now();
        $person->save();
    }

    public function isMaintenancePerson($customerType): bool
    {
        return ($customerType !== null && $customerType !== 'customer' && $customerType === 'maintenance');
    }

    public function createPersonDataFromRequest(Request $request): array
    {
        return [
            'title' => $request->get('title'),
            'education' => $request->get('education'),
            'name' => $request->get('name'),
            'surname' => $request->get('surname'),
            'birth_date' => $request->get('birth_date'),
            'sex' => $request->get('sex'),
            'is_customer' => $request->get('is_customer') ?? 0,
        ];
    }

    public function getById($personId): Person
    {
        /** @var Person $model */
        $model = $this->person->newQuery()->find($personId);
        return $model;
    }

    public function create($personData)
    {
        return $this->person->newQuery()->create($personData);
    }

    public function update($person, $personData)
    {
        foreach ($personData as $key => $value) {
            $person->$key = $value;
        }

        $person->save();
        $person->fresh();

        return $person;
    }

    public function delete($person)
    {
        $person->delete();
        return $person;
    }

    public function getAll($limit = null, $customerType = 1)
    {
        return $this->person->newQuery()->with([
            'addresses' => function ($q) {
                return $q->where('is_primary', 1);
            },
            'addresses.city',
            'devices',
        ])->where('is_customer', $customerType)->paginate($limit);
    }

    public function createFromRequest(Request $request): Model
    {
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
            'email' => $request->get('email') ?? "",
            'phone' => $request->get('phone') ?? "",
            'street' => $request->get('street') ?? "",
            'is_primary' => 1,
        ];

        $address = $addressService->instantiate($addressParams);
        $addressService->assignAddressToOwner($person, $address);

        return $person;
    }

    public function getByPhoneNumber($phoneNumber):?Person
    {
        return $this->person->newQuery()->whereHas('addresses', fn($q) => $q->where('phone', $phoneNumber))
            ->first();
    }
}
