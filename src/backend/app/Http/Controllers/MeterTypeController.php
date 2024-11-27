<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeterTypeCreateRequest;
use App\Http\Requests\MeterTypeUpdateRequest;
use App\Http\Resources\ApiResource;
use App\Services\MeterTypeService;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

/**
 * @group   MeterTypes
 * Class MeterTypeController
 */
class MeterTypeController extends Controller {
    use SoftDeletes;

    public function __construct(private MeterTypeService $meterTypeService) {}

    /**
     * List.
     *
     * @responseFile responses/metertypes/meter.types.list.json
     *
     * @return ApiResource
     */
    public function index(Request $request): ApiResource {
        $limit = $request->get('limit');

        return ApiResource::make($this->meterTypeService->getAll($limit));
    }

    /**
     * Store
     * Creates a new meter type.
     *
     * @bodyParam online int required
     * @bodyParam phase int required
     * @bodyParam max_current int required
     *
     * @param MeterTypeCreateRequest $request
     *
     * @return ApiResource
     */
    public function store(MeterTypeCreateRequest $request) {
        $meterTypeData = $request->only(['online', 'phase', 'max_current']);

        return ApiResource::make($this->meterTypeService->create($meterTypeData));
    }

    /**
     * Detail.
     *
     * @bodyParam id int required
     *
     * @param int $meterTypeId
     *
     * @return ApiResource
     */
    public function show($meterTypeId): ApiResource {
        return ApiResource::make($this->meterTypeService->getById($meterTypeId));
    }

    /**
     * Update
     * Updates the given meter type.
     *
     * @urlParam  id required
     *
     * @bodyParam online int required
     * @bodyParam phase int required
     * @bodyParam max_current int required
     *
     * @param MeterTypeUpdateRequest $request
     * @param int                    $meterTypeId
     *
     * @return ApiResource
     */
    public function update(MeterTypeUpdateRequest $request, $meterTypeId): ApiResource {
        $meterType = $this->meterTypeService->getById($meterTypeId);
        $meterTypeData = $request->only(['online', 'phase', 'max_current']);

        return ApiResource::make($this->meterTypeService->update($meterType, $meterTypeData));
    }
}
