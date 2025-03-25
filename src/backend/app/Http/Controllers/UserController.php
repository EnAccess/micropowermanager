<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAdminRequest;
use App\Http\Resources\ApiResource;
use App\Models\User;
use App\Services\CompanyDatabaseService;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller {
    public function __construct(
        private UserService $userService,
        private CompanyDatabaseService $companyDatabaseService,
    ) {}

    public function index(Request $request): ApiResource {
        $companyId = auth()->user()->company_id;
        $users = $this->userService->list($companyId);

        return new ApiResource($users);
    }

    public function store(CreateAdminRequest $request) {
        $user = $this->userService->create($request->only(['name', 'password', 'email']));

        return ApiResource::make($user->toArray());
    }

    public function show(User $user) {
        return new ApiResource($this->userService->get($user->id));
    }

    public function update(User $user, Request $request): ApiResource {
        $this->userService->update($user, $request->all());

        return new ApiResource($user->fresh());
    }
}
