<?php

namespace App\Http\Controllers;

use App\Http\Requests\SubConnectionTypeCreateRequest;
use App\Http\Resources\ApiResource;
use App\Services\SubConnectionTypeService;
use Illuminate\Http\Request;

class SubConnectionTypeController extends Controller {
    public function __construct(private SubConnectionTypeService $subConnectionTypeService) {}

    public function index(Request $request, ?int $connectionTypeId = null): ApiResource {
        $limit = $request->get('limit');

        if ($connectionTypeId !== null) {
            return ApiResource::make($this->subConnectionTypeService->getSubConnectionTypesByConnectionTypeId($connectionTypeId, $limit));
        }

        return ApiResource::make($this->subConnectionTypeService->getAll($limit));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param SubConnectionTypeCreateRequest $request
     *
     * @return ApiResource
     */
    public function store(SubConnectionTypeCreateRequest $request): ApiResource {
        $subConnectionTypeData = $request->only(['name', 'connection_type_id', 'tariff_id']);

        return ApiResource::make($this->subConnectionTypeService->create($subConnectionTypeData));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param int $subConnectionTypeId
     *
     * @return ApiResource
     */
    public function update(Request $request, $subConnectionTypeId): ApiResource {
        $subConnectionType = $this->subConnectionTypeService->getById($subConnectionTypeId);
        $subConnectionTypeData = $request->only(['name', 'tariff_id']);

        return ApiResource::make($this->subConnectionTypeService->update($subConnectionType, $subConnectionTypeData));
    }
}
