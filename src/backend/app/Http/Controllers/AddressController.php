<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\AddressesService;
use App\Services\PersonAddressService;
use App\Services\PersonService;

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
        return ApiResource::make($this->addressService->getAll());
    }

    public function show($id): ApiResource
    {
        return ApiResource::make($this->addressService->getById($id));
    }
}
