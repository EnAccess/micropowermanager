<?php

namespace Inensus\SteamaMeter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\SteamaMeter\Http\Resources\SteamaResource;
use Inensus\SteamaMeter\Models\SteamaSmsFeedbackWord;
use Inensus\SteamaMeter\Services\SteamaSmsFeedbackWordService;

class SteamaSmsFeedbackController extends Controller {
    public function __construct(private SteamaSmsFeedbackWordService $smsFeedbackWorkService) {}

    public function index(): SteamaResource {
        return new SteamaResource($this->smsFeedbackWorkService->getSmsFeedbackWords());
    }

    public function update(SteamaSmsFeedbackWord $smsFeedbackWord, Request $request): SteamaResource {
        return new SteamaResource($this->smsFeedbackWorkService->updateSmsFeedbackWord($smsFeedbackWord, $request->all()));
    }
}
