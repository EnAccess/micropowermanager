<?php

namespace App\Plugins\SwiftaPaymentProvider\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Plugins\SwiftaPaymentProvider\Services\SwiftaAuthenticationService;
use Illuminate\Routing\Controller;

class SwiftaAuthenticationController extends Controller {
    public function __construct(private SwiftaAuthenticationService $swiftaAuthenticationService) {}

    public function show(): ApiResource {
        return ApiResource::make($this->swiftaAuthenticationService->getSwiftaAuthentication());
    }
}
