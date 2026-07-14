<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\ConnectionTypeService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;

class ConnectionTypeController extends Controller {
    public function __construct(private ConnectionTypeService $connectionTypeService) {}

    public function index(Request $request): ApiResource {
        $limit = $request->input('limit');

        return ApiResource::make($this->connectionTypeService->getAll($limit));
    }

    /**
     * List connection types (customer registration app).
     *
     * Alias of `GET /api/connection-types` for the customer registration app.
     *
     * @deprecated use `GET /api/connection-types` instead
     */
    #[Group('Customer Registration App')]
    public function indexForCustomerRegistrationApp(Request $request): ApiResource {
        return ApiResource::make($this->connectionTypeService->getAll($request->input('limit')));
    }

    public function show(int $connectionTypeId, Request $request): ApiResource {
        $meterCountRelation = $request->input('meter_count');

        if ($meterCountRelation) {
            return ApiResource::make($this->connectionTypeService->getByIdWithMeterCountRelation($connectionTypeId));
        }

        return ApiResource::make($this->connectionTypeService->getById($connectionTypeId));
    }

    public function store(Request $request): ApiResource {
        $connectionTypeData = $request->all();

        return ApiResource::make($this->connectionTypeService->create($connectionTypeData));
    }

    public function update(int $connectionTypeId, Request $request): ApiResource {
        $connectionTypeData = $request->all();
        $connectionType = $this->connectionTypeService->getById($connectionTypeId);

        return ApiResource::make($this->connectionTypeService->update($connectionType, $connectionTypeData));
    }
}
