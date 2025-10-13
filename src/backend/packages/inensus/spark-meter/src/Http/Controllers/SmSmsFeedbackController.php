<?php

namespace Inensus\SparkMeter\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inensus\SparkMeter\Http\Resources\SparkResource;
use Inensus\SparkMeter\Models\SmSmsFeedbackWord;
use Inensus\SparkMeter\Services\SmSmsFeedbackWordService;

class SmSmsFeedbackController extends Controller {
    public function __construct(private SmSmsFeedbackWordService $smsFeedbackWorkService) {}

    public function index(): SparkResource {
        return new SparkResource($this->smsFeedbackWorkService->getSmsFeedbackWords());
    }

    public function update(SmSmsFeedbackWord $smsFeedbackWord, Request $request): SparkResource {
        return new SparkResource($this->smsFeedbackWorkService->updateSmsFeedbackWord($smsFeedbackWord, $request->all()));
    }
}
