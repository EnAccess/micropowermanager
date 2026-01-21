<?php

namespace App\Plugins\SteamaMeter\Http\Controllers;

use App\Plugins\SteamaMeter\Http\Resources\SteamaResource;
use App\Plugins\SteamaMeter\Models\SteamaSmsFeedbackWord;
use App\Plugins\SteamaMeter\Services\SteamaSmsFeedbackWordService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class SteamaSmsFeedbackController extends Controller {
    public function __construct(private SteamaSmsFeedbackWordService $smsFeedbackWorkService) {}

    public function index(): SteamaResource {
        return new SteamaResource($this->smsFeedbackWorkService->getSmsFeedbackWords());
    }

    public function update(SteamaSmsFeedbackWord $smsFeedbackWord, Request $request): SteamaResource {
        return new SteamaResource($this->smsFeedbackWorkService->updateSmsFeedbackWord($smsFeedbackWord, $request->all()));
    }
}
