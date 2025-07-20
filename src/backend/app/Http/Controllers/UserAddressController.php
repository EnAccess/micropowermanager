<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAddressRequest;
use App\Http\Resources\ApiResource;
use App\Models\User;
use App\Services\UserAddressService;
use Illuminate\Http\Request;

class UserAddressController extends Controller {
    private UserAddressService $userAddressService;

    public function __construct(UserAddressService $userAddressService) {
        $this->userAddressService = $userAddressService;
    }

    public function store(User $user, CreateAddressRequest $request): ApiResource {
        return new ApiResource($this->userAddressService->update($user, $request->all()));
    }

    public function admin(User $user, Request $request): ApiResource {
        $address = $user->addressDetails()->first();

        return new ApiResource($address);
    }

    public function update(User $user, CreateAddressRequest $request): ApiResource {
        return new ApiResource($this->userAddressService->update($user, $request->all()));
    }
}
