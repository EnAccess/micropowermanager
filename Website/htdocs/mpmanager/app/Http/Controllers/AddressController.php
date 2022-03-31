<?php

namespace App\Http\Controllers;


use App\Http\Requests\CreateAddressRequest;
use App\Models\Address\Address;
use App\Exceptions\ValidationException;
use App\Http\Resources\ApiResource;
use App\Models\Person\Person;
use App\Models\User;

use App\Services\PersonAddressService;
use App\Services\PersonService;
use App\Services\AddressesService;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    public function __construct(
        private AddressesService $addressService,
        private PersonService $personService,
        private PersonAddressService $personAddressService
    ) {

    }
    public function index(): ApiResource
    {
        return ApiResource::make($this->addressService->getAddressList());
    }

    public function show($id): ApiResource
    {
        return ApiResource::make($this->addressService->getAddressById($id));
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
