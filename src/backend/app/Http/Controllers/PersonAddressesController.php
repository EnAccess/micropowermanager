<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAddressRequest;
use App\Http\Resources\ApiResource;
use App\Services\AddressesService;
use App\Services\PersonAddressService;
use App\Services\PersonService;
use Illuminate\Validation\ValidationException;

class PersonAddressesController extends Controller {
    public function __construct(
        private AddressesService $addressService,
        private PersonService $personService,
        private PersonAddressService $personAddressService,
    ) {}

    /**
     * List person addresses.
     *
     * A list of registered addresses for that person.
     */
    public function show(int $personId): ApiResource {
        $person = $this->personService->getById($personId);

        return ApiResource::make($this->personAddressService->getPersonAddresses($person));
    }

    public function store(int $personId, CreateAddressRequest $request): ApiResource {
        $person = $this->personService->getById($personId);
        $addressData = $this->addressService->createAddressDataFromRequest($request);
        $address = $this->addressService->make($addressData);
        $this->personAddressService->setAssignee($person);
        $this->personAddressService->setAssigned($address);

        if ($addressData['is_primary']) {
            $this->personAddressService->setOldPrimaryAddressToNotPrimary();
        }
        $this->personAddressService->assign();
        $this->addressService->save($address);
        $this->personService->updatePersonUpdatedDate($person);

        return new ApiResource($this->addressService->getStoredAddressWithCityRelation($address->id));
    }

    public function update(int $personId, CreateAddressRequest $request): ApiResource {
        $person = $this->personService->getById($personId);
        $address = $this->addressService->getById($request->input('id', -1));
        $addressData = $this->addressService->createAddressDataFromRequest($request);
        $this->personAddressService->setAssignee($person);
        $this->personAddressService->setAssigned($address);

        if ($addressData['is_primary']) {
            $this->personAddressService->setOldPrimaryAddressToNotPrimary();
        }
        $this->addressService->update($address, $addressData);
        $this->personService->updatePersonUpdatedDate($person);

        return new ApiResource($this->addressService->getStoredAddressWithCityRelation($address->id));
    }

    public function destroy(int $personId, int $addressId): ApiResource {
        $person = $this->personService->getById($personId);
        $address = $person->addresses()->findOrFail($addressId);

        if ($address->is_primary) {
            throw ValidationException::withMessages(['address' => ['The primary address cannot be deleted.']]);
        }

        $this->addressService->delete($address);
        $this->personService->updatePersonUpdatedDate($person);

        return ApiResource::make($address);
    }
}
