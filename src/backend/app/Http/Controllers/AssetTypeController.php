<?php

namespace App\Http\Controllers;

use App\Http\Requests\AssetTypeCreateRequest;
use App\Http\Requests\AssetTypeUpdateRequest;
use App\Http\Resources\ApiResource;
use App\Models\AssetType;
use App\Services\ApplianceTypeService;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;

class AssetTypeController extends Controller {
    use SoftDeletes;

    /**
     * @var AssetType
     */
    private $applianceTypeService;

    public function __construct(ApplianceTypeService $applianceTypeService) {
        $this->applianceTypeService = $applianceTypeService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): ApiResource {
        return new ApiResource($this->applianceTypeService->getApplianceTypes($request));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(AssetTypeCreateRequest $request): ApiResource {
        $appliance = $this->applianceTypeService->createApplianceType($request);

        return new ApiResource($appliance);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(AssetTypeUpdateRequest $request, AssetType $assetType): ApiResource {
        $appliance = $this->applianceTypeService->updateApplianceType($request, $assetType);

        return new ApiResource($appliance);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @throws \Exception
     */
    public function destroy(AssetType $assetType): ApiResource {
        return new ApiResource(
            $this->applianceTypeService->deleteApplianceType($assetType)
        );
    }
}
