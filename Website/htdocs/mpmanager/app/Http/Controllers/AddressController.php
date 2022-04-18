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

}
