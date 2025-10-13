<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\SmsVariableDefaultValueService;

class SmsVariableDefaultValueController extends Controller {
    public function __construct(private SmsVariableDefaultValueService $smsVariableDefaultSValueService) {}

    public function index(): ApiResource {
        return new ApiResource($this->smsVariableDefaultSValueService->getSmsVariableDefaultValues());
    }
}
