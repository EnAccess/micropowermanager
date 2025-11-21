<?php

namespace Inensus\SparkMeter\Http\Controllers;

use App\Http\Controllers\Controller;
use Inensus\SparkMeter\Http\Resources\SparkResource;
use Inensus\SparkMeter\Services\SmSmsVariableDefaultValueService;

class SmSmsVariableDefaultValueController extends Controller {
    public function __construct(private SmSmsVariableDefaultValueService $smsVariableDefaultSValueService) {}

    public function index(): SparkResource {
        return new SparkResource($this->smsVariableDefaultSValueService->getSmsVariableDefaultValues());
    }
}
