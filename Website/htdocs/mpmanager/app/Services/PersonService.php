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

class PersonService
{

    public function __construct(private SessionService $sessionService,private Person $person)
    {
        $this->sessionService->setModel($person);
    }

    //get person by id
    public function getPersonById(int $id): ?Person
    {
        return $this->person->newQuery()->findOrFail($id);
    }

    public function createFromRequest(Request $request): Model
    {

        $person = $this->person::create(
            request()->only(
                [
                'title',
                'education',
                'name',
                'surname',
                'birth_date',
                'sex',
                'is_customer',
                ]
            )
        );


        $countryService = App::make(CountryService::class);
        $country = $countryService->getByCode(request('nationality') ?? 'TZ');
        if ($country !== null) {
            $person = $this->addCitizenship($person, $country);
        }

        $addressService = App::make(AddressService::class);

        $addressParams = [
            'city_id' => request('city_id') ?? 1,
            'email' => request('email') ?? "",
            'phone' => request('phone') ?? "",
            'street' => request('street') ?? "",
            'is_primary' => 1,
        ];

        $address = $addressService->instantiate($addressParams);

        $addressService->assignAddressToOwner($person, $address);
        return $person;
    }


    // associates the person with a country
    public function addCitizenship(Person $person, Country $country): Model
    {
        return $person->citizenship()->associate($country);
    }

    //assign an address to the person
    /**
     * @return Model|false
     */
    public function addAddress(Person $person, Address $address)
    {
        return $person->addresses()->save($address);
    }

    public function getDetails(int $personID, bool $allRelations = false)
    {
        if (!$allRelations) {
            return $this->person->find($personID);
        }
        return $this->person::with(
            [
            'addresses' =>
                function ($q) {
                    return $q->orderBy('is_primary')->get();
                },
            'citizenship',
            'roleOwner.definitions',
            'meters.meter',
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
        if ($paginate === 1) {
            return $this->person::with('addresses.city', 'meters.meter')->whereHas(
                'addresses',
                function ($q) use ($searchTerm) {
                    $q->where('phone', 'LIKE', '%' . $searchTerm . '%');
                }
            )->orWhereHas(
                'meters.meter',
                function ($q) use ($searchTerm) {
                        $q->where('serial_number', 'LIKE', '%' . $searchTerm . '%');
                }
            )->orWhere('name', 'LIKE', '%' . $searchTerm . '%')
                ->orWhere('surname', 'LIKE', '%' . $searchTerm . '%')->paginate(15);
        }

        return $this->person::with('addresses.city', 'meters.meter')->whereHas(
            'addresses',
            function ($q) use ($searchTerm) {
                $q->where('phone', 'LIKE', '%' . $searchTerm . '%');
            }
        )->orWhereHas(
            'meters.meter',
            function ($q) use ($searchTerm) {
                    $q->where('serial_number', 'LIKE', '%' . $searchTerm . '%');
            }
        )->orWhere('name', 'LIKE', '%' . $searchTerm . '%')
            ->orWhere('surname', 'LIKE', '%' . $searchTerm . '%')->get();
    }


    public function createMaintenancePerson(Request $request): Person
    {
        $this->person->is_customer = 0;
        $this->person->name = $request->get('name');
        $this->person->surname = $request->get('surname');
        $this->person->sex = $request->get('sex');

        $this->person->save();


        $addressService = App::make(AddressService::class);

        $addressParams = [
            'city_id' => $request->get('city_id') ?? 1,
            'email' => $request->get('email') ?? '',
            'phone' => $request->get('phone') ?? '',
            'street' => $request->get('street') ?? '',
            'is_primary' => 1,
        ];

        $address = $addressService->instantiate($addressParams);

        $addressService->assignAddressToOwner($this->person, $address);


        $maintenance = App::make(MaintenanceUsers::class);

        $maintenance->person_id = $this->person->id;
        $maintenance->mini_grid_id = $request->get('mini_grid_id');
        $maintenance->save();

        return $this->person;
    }

    public function livingInCluster(int $clusterId)
    {
        return $this->person->livingInClusterQuery($clusterId);
    }

    public function getBulkDetails(array $peopleId): Builder
    {
        return $this->person::with(
            [
                'addresses' => fn ($q) => $q->where('is_primary', '=', 1),
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

}
