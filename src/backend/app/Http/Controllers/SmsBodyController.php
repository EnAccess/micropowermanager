<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\SmsBodyService;
use Illuminate\Http\Request;

class SmsBodyController extends Controller {
    private SmsBodyService $smsBodyService;

    public function __construct(SmsBodyService $smsBodyService) {
        $this->smsBodyService = $smsBodyService;
    }

    public function index(): ApiResource {
        return new ApiResource($this->smsBodyService->getSmsBodies());
    }

    public function update(Request $request): ApiResource {
        return new ApiResource($this->smsBodyService->updateSmsBodies($request->all()));
    }
}
