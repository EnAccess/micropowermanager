<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\AddressesService;

class AddressController extends Controller {
    public function __construct(
        private AddressesService $addressService,
    ) {}

    public function index(): ApiResource {
        return ApiResource::make($this->addressService->getAll());
    }

    public function show($id): ApiResource {
        return ApiResource::make($this->addressService->getById($id));
    }
}
