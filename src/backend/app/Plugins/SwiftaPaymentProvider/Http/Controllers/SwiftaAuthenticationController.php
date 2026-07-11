<?php

namespace App\Plugins\SwiftaPaymentProvider\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Plugins\SwiftaPaymentProvider\Services\SwiftaAuthenticationService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Routing\Controller;

#[Group('Plugins / Swifta')]
class SwiftaAuthenticationController extends Controller {
    public function __construct(private SwiftaAuthenticationService $swiftaAuthenticationService) {}

    public function show(): ApiResource {
        return ApiResource::make($this->swiftaAuthenticationService->getSwiftaAuthentication());
    }
}
