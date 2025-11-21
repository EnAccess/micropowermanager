<?php

namespace Inensus\SteamaMeter\Http\Controllers;

use Inensus\SteamaMeter\Http\Resources\SteamaResource;
use Inensus\SteamaMeter\Services\SteamaSmsVariableDefaultValueService;
use App\Http\Controllers\Controller;

class SteamaSmsVariableDefaultValueController extends Controller {
    public function __construct(private SteamaSmsVariableDefaultValueService $smsVariableDefaultSValueService) {}

    public function index(): SteamaResource {
        return new SteamaResource($this->smsVariableDefaultSValueService->getSmsVariableDefaultValues());
    }
}
