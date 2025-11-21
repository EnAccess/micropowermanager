<?php

namespace Inensus\SteamaMeter\Http\Controllers;

use App\Http\Controllers\Controller;
use Inensus\SteamaMeter\Http\Resources\SteamaResource;
use Inensus\SteamaMeter\Services\SteamaSmsVariableDefaultValueService;

class SteamaSmsVariableDefaultValueController extends Controller {
    public function __construct(private SteamaSmsVariableDefaultValueService $smsVariableDefaultSValueService) {}

    public function index(): SteamaResource {
        return new SteamaResource($this->smsVariableDefaultSValueService->getSmsVariableDefaultValues());
    }
}
