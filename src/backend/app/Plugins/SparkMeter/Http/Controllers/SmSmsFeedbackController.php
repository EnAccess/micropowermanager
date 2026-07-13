<?php

namespace App\Plugins\SparkMeter\Http\Controllers;

use App\Plugins\SparkMeter\Http\Resources\SparkResource;
use App\Plugins\SparkMeter\Models\SmSmsFeedbackWord;
use App\Plugins\SparkMeter\Services\SmSmsFeedbackWordService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

#[Group('Plugins / Spark Meter')]
class SmSmsFeedbackController extends Controller {
    public function __construct(private SmSmsFeedbackWordService $smsFeedbackWorkService) {}

    public function index(): SparkResource {
        return new SparkResource($this->smsFeedbackWorkService->getSmsFeedbackWords());
    }

    public function update(SmSmsFeedbackWord $smsFeedbackWord, Request $request): SparkResource {
        return new SparkResource($this->smsFeedbackWorkService->updateSmsFeedbackWord($smsFeedbackWord, $request->all()));
    }
}
