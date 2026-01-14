<?php

namespace App\Plugins\SteamaMeter\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Plugins\SteamaMeter\Http\Resources\SteamaResource;
use App\Plugins\SteamaMeter\Services\SteamaSmsVariableDefaultValueService;

class SteamaSmsVariableDefaultValueController extends Controller {
    public function __construct(private SteamaSmsVariableDefaultValueService $smsVariableDefaultSValueService) {}

    public function index(): SteamaResource {
        return new SteamaResource($this->smsVariableDefaultSValueService->getSmsVariableDefaultValues());
    }
}
