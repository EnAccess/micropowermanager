<?php

namespace Inensus\SparkMeter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\SparkMeter\Http\Resources\SparkResource;
use Inensus\SparkMeter\Services\SmSmsBodyService;

class SmSmsBodyController extends Controller {
    private $smsBodyService;

    public function __construct(SmSmsBodyService $smsBodyService) {
        $this->smsBodyService = $smsBodyService;
    }

    public function index(): SparkResource {
        return new SparkResource($this->smsBodyService->getSmsBodies());
    }

    public function update(Request $request): SparkResource {
        return new SparkResource($this->smsBodyService->updateSmsBodies($request->all()));
    }
}
