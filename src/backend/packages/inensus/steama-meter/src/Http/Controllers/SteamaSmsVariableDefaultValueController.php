<?php

namespace Inensus\SteamaMeter\Http\Controllers;

use Inensus\SteamaMeter\Http\Resources\SteamaResource;
use Inensus\SteamaMeter\Services\SteamaSmsVariableDefaultValueService;
use Inensus\Ticket\Http\Controllers\Controller;

class SteamaSmsVariableDefaultValueController extends Controller {
    private $smsVariableDefaultSValueService;

    public function __construct(SteamaSmsVariableDefaultValueService $smsVariableDefaultSValueService) {
        $this->smsVariableDefaultSValueService = $smsVariableDefaultSValueService;
    }

    public function index(): SteamaResource {
        return new SteamaResource($this->smsVariableDefaultSValueService->getSmsVariableDefaultValues());
    }
}
