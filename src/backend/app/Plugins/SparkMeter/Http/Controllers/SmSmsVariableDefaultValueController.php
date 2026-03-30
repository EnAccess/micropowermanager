<?php

namespace App\Plugins\SparkMeter\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Plugins\SparkMeter\Http\Resources\SparkResource;
use App\Plugins\SparkMeter\Services\SmSmsVariableDefaultValueService;

class SmSmsVariableDefaultValueController extends Controller {
    public function __construct(private SmSmsVariableDefaultValueService $smsVariableDefaultSValueService) {}

    public function index(): SparkResource {
        return new SparkResource($this->smsVariableDefaultSValueService->getSmsVariableDefaultValues());
    }
}
