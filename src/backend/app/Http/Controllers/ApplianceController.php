<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateApplianceRequest;
use App\Http\Requests\UpdateApplianceRequest;
use App\Http\Resources\ApiResource;
use App\Models\Appliance;
use App\Services\ApplianceService;
use Illuminate\Http\Request;

class ApplianceController extends Controller {
    public function __construct(private ApplianceService $applianceService) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): ApiResource {
        return ApiResource::make($this->applianceService->getAppliances($request));
    }

    public function store(CreateApplianceRequest $request): ApiResource {
        $this->applianceService->createAppliance($request);

        return ApiResource::make($this->applianceService->getAppliances($request));
    }

    public function update(UpdateApplianceRequest $request, Appliance $appliance): ApiResource {
        $this->applianceService->updateAppliance($request, $appliance);

        return ApiResource::make($this->applianceService->getAppliances($request));
    }

    public function destroy(Appliance $appliance): ApiResource {
        return ApiResource::make($this->applianceService->deleteAppliance($appliance));
    }
}
