<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\ConnectionTypeService;
use Illuminate\Http\Request;

class ConnectionTypeController extends Controller {
    public function __construct(private ConnectionTypeService $connectionTypeService) {}

    public function index(Request $request): ApiResource {
        $limit = $request->get('limit');

        return ApiResource::make($this->connectionTypeService->getAll($limit));
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
