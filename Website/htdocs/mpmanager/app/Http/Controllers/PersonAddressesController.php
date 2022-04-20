<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAddressRequest;
use App\Http\Resources\ApiResource;
use App\Models\Person\Person;
use App\Services\AddressesService;
use App\Services\PersonAddressService;
use App\Services\PersonService;
use Illuminate\Http\Request;

class PersonAddressesController extends Controller
{
    public function __construct(
        private AddressesService $addressService,
        private PersonService $personService,
        private PersonAddressService $personAddressService
    ) {
    }

    /**
     * Addresses
     * A list of registered addresses for that person
     *
     * @bodyParam    person int required the ID of the person. Example: 2
     * @responseFile responses/people/person.addresses.list.json
     * @param int $personId
     *
     * @return ApiResource
     *
     * @apiResourceModel \App\Models\Person\Person
     */
    public function show(int $personId): ApiResource
    {
        $person = $this->personService->getPersonById($personId);

        return ApiResource::make($this->personAddressService->getPersonAddresses($person));
    }

    public function store(int $personId, CreateAddressRequest $request): ApiResource
    {
        $person = $this->personService->getPersonById($personId);
        $addressData = $this->addressService->createAddressDataFromRequest($request);
        $address = $this->addressService->makeAddress($addressData);
        $this->personAddressService->setPerson($person);
        $this->personAddressService->setAddress($address);

        if ($addressData['is_primary']) {
            $this->personAddressService->setOldPrimaryAddressToNotPrimary();
        }
        $this->personAddressService->assignAddressToPerson();
        $this->addressService->saveAddress($address);
        $this->personService->updatePersonUpdatedDate($person);

        return new ApiResource($this->addressService->getStoredAddressWithCityRelation($address->id));
    }

    public function update(int $personId, CreateAddressRequest $request): ApiResource
    {
        $person = $this->personService->getPersonById($personId);
        $address = $this->addressService->getAddressById($request->input('id') ?? -1);
        $addressData = $this->addressService->createAddressDataFromRequest($request);
        $this->personAddressService->setPerson($person);
        $this->personAddressService->setAddress($address);

        if ($addressData['is_primary']) {
            $this->personAddressService->setOldPrimaryAddressToNotPrimary();
        }
        $this->addressService->updateAddress($address, $addressData);
        $this->personService->updatePersonUpdatedDate($person);

        return new ApiResource($this->addressService->getStoredAddressWithCityRelation($address->id));
    }
}
