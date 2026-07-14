<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubConnectionTypeCreateRequest;
use App\Http\Resources\ApiResource;
use App\Services\SubConnectionTypeService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

class SubConnectionTypeController extends Controller {
    public function __construct(private SubConnectionTypeService $subConnectionTypeService) {}

    public function index(Request $request, ?int $connectionTypeId = null): ApiResource {
        $limit = $request->input('limit');

        if ($connectionTypeId !== null) {
            return ApiResource::make($this->subConnectionTypeService->getSubConnectionTypesByConnectionTypeId($connectionTypeId, $limit));
        }

        return ApiResource::make($this->subConnectionTypeService->getAll($limit));
    }

    /**
     * List sub connection types (customer registration app).
     *
     * Alias of `GET /api/sub-connection-types` for the customer registration app.
     *
     * @deprecated use `GET /api/sub-connection-types` instead
     */
    #[Group('Customer Registration App')]
    public function indexForCustomerRegistrationApp(Request $request): ApiResource {
        return ApiResource::make($this->subConnectionTypeService->getAll($request->input('limit')));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function store(SubConnectionTypeCreateRequest $request): ApiResource {
        $subConnectionTypeData = $request->only(['name', 'connection_type_id', 'tariff_id']);

        return ApiResource::make($this->subConnectionTypeService->create($subConnectionTypeData));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, int $subConnectionTypeId): ApiResource {
        $subConnectionType = $this->subConnectionTypeService->getById($subConnectionTypeId);
        $subConnectionTypeData = $request->only(['name', 'tariff_id']);

        return ApiResource::make($this->subConnectionTypeService->update($subConnectionType, $subConnectionTypeData));
    }
}
