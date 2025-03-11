<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssetTypeCreateRequest;
use App\Http\Requests\AssetTypeUpdateRequest;
use App\Http\Resources\ApiResource;
use App\Models\AssetType;
use App\Services\ApplianceTypeService;
use Illuminate\Http\Request;

class AssetTypeController extends Controller {
    /**
     * @var ApplianceTypeService
     */
    private $applianceTypeService;

    public function __construct(ApplianceTypeService $applianceTypeService) {
        $this->applianceTypeService = $applianceTypeService;
    }

    /**
     * Display a listing of the resource.
     *
     * @return ApiResource
     */
    public function index(Request $request): ApiResource {
        return new ApiResource($this->applianceTypeService->getApplianceTypes($request));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param AssetTypeCreateRequest $request
     *
     * @return ApiResource
     */
    public function store(AssetTypeCreateRequest $request): ApiResource {
        $appliance = $this->applianceTypeService->createApplianceType($request->validated());

        return new ApiResource($appliance);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param AssetTypeUpdateRequest $request
     * @param AssetType              $assetType
     *
     * @return ApiResource
     */
    public function update(AssetTypeUpdateRequest $request, AssetType $assetType): ApiResource {
        $appliance = $this->applianceTypeService->updateApplianceType($request->validated(), $assetType);

        return new ApiResource($appliance);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param AssetType $assetType
     *
     * @return ApiResource
     *
     * @throws \Exception
     */
    public function destroy(AssetType $assetType): ApiResource {
        return new ApiResource(
            $this->applianceTypeService->deleteApplianceType($assetType)
        );
    }
}
