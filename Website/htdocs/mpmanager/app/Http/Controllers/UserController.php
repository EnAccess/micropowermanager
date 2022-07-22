<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAdminRequest;
use App\Http\Resources\ApiResource;
use App\Models\User;
use App\Services\IUserService;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __construct(private IUserService $userService)
    {
    }

    public function index(Request $request): ApiResource
    {
        $users = $this->userService->list();
        return new ApiResource($users);
    }

    public function store(CreateAdminRequest $request): ApiResource
    {
        $user = $this->userService->create($request->only(['name', 'password', 'email']));


        return ApiResource::make($user);

    }

    public function show(User $user)
    {
        return new ApiResource($this->userService->get($user->id));
    }

    public function update(User $user, Request $request): ApiResource
    {
        $this->userService->update($user, $request->all());
        return new ApiResource($user->fresh());
    }
}
