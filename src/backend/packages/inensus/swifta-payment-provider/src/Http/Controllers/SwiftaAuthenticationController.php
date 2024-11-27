<?php

namespace Inensus\SwiftaPaymentProvider\Http\Controllers;

use App\Http\Resources\ApiResource;
use Illuminate\Routing\Controller;
use Inensus\SwiftaPaymentProvider\Services\SwiftaAuthenticationService;

class SwiftaAuthenticationController extends Controller {
    public function __construct(private SwiftaAuthenticationService $swiftaAuthenticationService) {}

    public function show(): ApiResource {
        return ApiResource::make($this->swiftaAuthenticationService->getSwiftaAuthentication());
    }
}
