<?php

namespace App\Http\Controllers;

use App\Http\Requests\MeterTypeCreateRequest;
use App\Http\Requests\MeterTypeUpdateRequest;
use App\Http\Resources\ApiResource;
use App\Services\MeterTypeService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

class MeterTypeController extends Controller {
    use SoftDeletes;

    public function __construct(private MeterTypeService $meterTypeService) {}

    /**
     * List meter types.
     */
    public function index(Request $request): ApiResource {
        $limit = $request->input('limit');

        return ApiResource::make($this->meterTypeService->getAll($limit));
    }

    /**
     * List meter types (customer registration app).
     *
     * Alias of `GET /api/meter-types` for the customer registration app.
     *
     * @deprecated use `GET /api/meter-types` instead
     */
    #[Group('Customer Registration App')]
    public function indexForCustomerRegistrationApp(Request $request): ApiResource {
        return ApiResource::make($this->meterTypeService->getAll($request->input('limit')));
    }

    /**
     * Create a meter type.
     *
     * @return ApiResource
     */
    public function store(MeterTypeCreateRequest $request) {
        $meterTypeData = $request->only(['online', 'phase', 'max_current']);

        return ApiResource::make($this->meterTypeService->create($meterTypeData));
    }

    /**
     * Get meter type details.
     */
    public function show(int $meterTypeId): ApiResource {
        return ApiResource::make($this->meterTypeService->getById($meterTypeId));
    }

    /**
     * Update a meter type.
     */
    public function update(MeterTypeUpdateRequest $request, int $meterTypeId): ApiResource {
        $meterType = $this->meterTypeService->getById($meterTypeId);
        $meterTypeData = $request->only(['online', 'phase', 'max_current']);

        return ApiResource::make($this->meterTypeService->update($meterType, $meterTypeData));
    }
}
