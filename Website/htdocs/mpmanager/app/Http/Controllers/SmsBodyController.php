<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Resources\ApiResource;
use App\Services\SmsBodyService;

class SmsBodyController extends Controller
{
    private $smsBodyService;

    public function __construct(SmsBodyService $smsBodyService)
    {
        $this->smsBodyService = $smsBodyService;
    }

    public function index(): ApiResource
    {
        return new ApiResource($this->smsBodyService->getSmsBodies());
    }
    public function update(Request $request): ApiResource
    {
        return new ApiResource($this->smsBodyService->updateSmsBodies($request->all()));
    }
}
