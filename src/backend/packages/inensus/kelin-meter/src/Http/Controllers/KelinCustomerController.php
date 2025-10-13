<?php

namespace Inensus\KelinMeter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\KelinMeter\Http\Resources\KelinCustomerResource;
use Inensus\KelinMeter\Http\Resources\KelinResource;
use Inensus\KelinMeter\Services\KelinCustomerService;

class KelinCustomerController extends Controller implements IBaseController {
    public function __construct(private KelinCustomerService $customerService) {}

    public function index(Request $request) {
        return KelinCustomerResource::collection($this->customerService->getCustomers($request));
    }

    public function sync() {
        return KelinCustomerResource::collection($this->customerService->sync());
    }

    public function checkSync(): KelinResource {
        return new KelinResource($this->customerService->syncCheck());
    }
}
