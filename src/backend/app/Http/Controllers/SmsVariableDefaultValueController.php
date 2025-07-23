<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\SmsVariableDefaultValueService;

class SmsVariableDefaultValueController extends Controller {
    private SmsVariableDefaultValueService $smsVariableDefaultSValueService;

    public function __construct(SmsVariableDefaultValueService $smsVariableDefaultSValueService) {
        $this->smsVariableDefaultSValueService = $smsVariableDefaultSValueService;
    }

    public function index(): ApiResource {
        return new ApiResource($this->smsVariableDefaultSValueService->getSmsVariableDefaultValues());
    }
}
