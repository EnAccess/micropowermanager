<?php

namespace App\Plugins\SparkMeter\Http\Controllers;

use App\Plugins\SparkMeter\Http\Resources\SparkResource;
use App\Plugins\SparkMeter\Services\SmSmsBodyService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SmSmsBodyController extends Controller {
    public function __construct(private SmSmsBodyService $smsBodyService) {}

    public function index(): SparkResource {
        return new SparkResource($this->smsBodyService->getSmsBodies());
    }

    public function update(Request $request): SparkResource {
        return new SparkResource($this->smsBodyService->updateSmsBodies($request->all()));
    }
}
