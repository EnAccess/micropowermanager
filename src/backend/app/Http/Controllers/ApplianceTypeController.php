<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApplianceTypeCreateRequest;
use App\Http\Requests\ApplianceTypeUpdateRequest;
use App\Http\Resources\ApiResource;
use App\Models\ApplianceType;
use App\Services\ApplianceTypeService;
use Illuminate\Http\Request;

class ApplianceTypeController extends Controller {
    public function __construct(private ApplianceTypeService $applianceTypeService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): ApiResource {
        return new ApiResource($this->applianceTypeService->getApplianceTypes($request));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ApplianceTypeCreateRequest $request): ApiResource {
        $appliance = $this->applianceTypeService->createApplianceType($request->validated());

        return new ApiResource($appliance);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(ApplianceTypeUpdateRequest $request, ApplianceType $applianceType): ApiResource {
        $appliance = $this->applianceTypeService->updateApplianceType($request->validated(), $applianceType);

        return new ApiResource($appliance);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     */
    public function destroy(ApplianceType $applianceType): ApiResource {
        $this->applianceTypeService->deleteApplianceType($applianceType);

        return new ApiResource(['message' => 'Appliance type deleted successfully']);
    }
}
