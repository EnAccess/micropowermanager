<?php

namespace App\Plugins\KelinMeter\Http\Controllers;

use App\Plugins\KelinMeter\Http\Resources\KelinCustomerResource;
use App\Plugins\KelinMeter\Http\Resources\KelinResource;
use App\Plugins\KelinMeter\Http\Resources\KelinSettingCollection;
use App\Plugins\KelinMeter\Services\KelinCustomerService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class KelinCustomerController extends Controller implements IBaseController {
    public function __construct(private KelinCustomerService $customerService) {}

    public function index(Request $request): KelinSettingCollection {
        return new KelinSettingCollection(KelinCustomerResource::collection($this->customerService->getCustomers($request)));
    }

    public function sync(): KelinSettingCollection {
        return new KelinSettingCollection(KelinCustomerResource::collection($this->customerService->sync()));
    }

    public function checkSync(): KelinResource {
        return new KelinResource($this->customerService->syncCheck());
    }
}
