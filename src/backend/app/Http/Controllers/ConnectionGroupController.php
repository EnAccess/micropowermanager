<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateConnectionGroupRequest;
use App\Http\Resources\ApiResource;
use App\Services\ConnectionGroupService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

class ConnectionGroupController {
    public function __construct(
        private ConnectionGroupService $connectionGroupService,
    ) {}

    public function index(Request $request): ApiResource {
        $limit = $request->input('per_page');

        return ApiResource::make($this->connectionGroupService->getAll($limit));
    }

    /**
     * List connection groups (customer registration app).
     *
     * Alias of `GET /api/connection-groups` for the customer registration app.
     *
     * @deprecated use `GET /api/connection-groups` instead
     */
    #[Group('Customer Registration App')]
    public function indexForCustomerRegistrationApp(Request $request): ApiResource {
        return ApiResource::make($this->connectionGroupService->getAll($request->input('per_page')));
    }

    public function show(int $connectionGroupId, Request $request): ApiResource {
        return ApiResource::make($this->connectionGroupService->getById($connectionGroupId));
    }

    public function store(CreateConnectionGroupRequest $request): ApiResource {
        $connectionGroupData = $request->all();

        return new ApiResource($this->connectionGroupService->create($connectionGroupData));
    }

    public function update(int $connectionGroupId, CreateConnectionGroupRequest $request): ApiResource {
        $connectionGroup = $this->connectionGroupService->getById($connectionGroupId);
        $connectionGroupData = $request->all();

        return ApiResource::make($this->connectionGroupService->update($connectionGroup, $connectionGroupData));
    }
}
