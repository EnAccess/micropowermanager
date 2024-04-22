<?php

namespace Inensus\AirtelPaymentProvider\Http\Controllers;

use App\Http\Resources\ApiResource;
use Illuminate\Routing\Controller;
use Inensus\AirtelPaymentProvider\Services\AirtelAuthenticationService;

class AirtelAuthenticationController extends Controller
{
    public function __construct(private AirtelAuthenticationService $airtelAuthenticationService)
    {
    }

    public function show(): ApiResource
    {
        return ApiResource::make($this->airtelAuthenticationService->getAirtelAuthentication());
    }

}