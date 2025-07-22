<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAdminRequest;
use App\Http\Resources\ApiResource;
use App\Models\User;
use App\Services\CompanyDatabaseService;
use App\Services\DatabaseProxyService;
use App\Services\UserService;
use Illuminate\Http\Request;

class UserController extends Controller {
    public function __construct(
        private UserService $userService,
        private DatabaseProxyService $databaseProxyService,
        private CompanyDatabaseService $companyDatabaseService,
    ) {}

    public function index(Request $request): ApiResource {
        $users = $this->userService->list();

        return new ApiResource($users);
    }

    public function store(CreateAdminRequest $request): ApiResource {
        $user = $this->userService->create($request->only(['name', 'password', 'email']));
        $companyDatabase = $this->companyDatabaseService->findByCompanyId($user->getCompanyId());
        $databaseProxyData = [
            'email' => $user->getEmail(),
            'fk_company_id' => $user->getCompanyId(),
            'fk_company_database_id' => $companyDatabase->getId(),
        ];
        $this->databaseProxyService->create($databaseProxyData);

        return ApiResource::make($user->toArray());
    }

    public function show(User $user): ApiResource {
        return new ApiResource($this->userService->get($user->id));
    }

    public function update(User $user, Request $request): ApiResource {
        $this->userService->update($user, $request->all());

        return new ApiResource($user->fresh());
    }
}
