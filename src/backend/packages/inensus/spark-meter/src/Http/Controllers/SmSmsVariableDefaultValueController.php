<?php

namespace Inensus\SparkMeter\Http\Controllers;

use Inensus\SparkMeter\Http\Resources\SparkResource;
use Inensus\SparkMeter\Services\SmSmsVariableDefaultValueService;
use Inensus\Ticket\Http\Controllers\Controller;

class SmSmsVariableDefaultValueController extends Controller {
    public function __construct(private SmSmsVariableDefaultValueService $smsVariableDefaultSValueService) {}

    public function index(): SparkResource {
        return new SparkResource($this->smsVariableDefaultSValueService->getSmsVariableDefaultValues());
    }
}
