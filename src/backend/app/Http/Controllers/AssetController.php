<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAssetRequest;
use App\Http\Requests\UpdateAssetRequest;
use App\Http\Resources\ApiResource;
use App\Models\Asset;
use App\Services\AssetService;
use Illuminate\Http\Request;

class AssetController extends Controller {
    public function __construct(private AssetService $assetService) {}

    /**
     * Display a listing of the resource.
     *
     * @return ApiResource
     */
    public function index(Request $request): ApiResource {
        return ApiResource::make($this->assetService->getAssets($request));
    }

    public function store(CreateAssetRequest $request): ApiResource {
        $this->assetService->createAsset($request);

        return ApiResource::make($this->assetService->getAssets($request));
    }

    public function update(UpdateAssetRequest $request, Asset $asset): ApiResource {
        $this->assetService->updateAsset($request, $asset);

        return ApiResource::make($this->assetService->getAssets($request));
    }

    public function destroy(Asset $asset): ApiResource {
        return ApiResource::make($this->assetService->deleteAsset($asset));
    }
}
