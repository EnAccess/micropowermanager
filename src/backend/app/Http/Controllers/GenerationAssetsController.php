<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\GenerationAssetsService;

class GenerationAssetsController extends Controller
{
    /**
     * @var GenerationAssetsService
     */
    private $generationAssetsService;

    /**
     * GenerationAssetsController constructor.
     *
     * @param GenerationAssetsService $generationAssetsService
     */
    public function __construct(GenerationAssetsService $generationAssetsService)
    {
        $this->generationAssetsService = $generationAssetsService;
    }

    public function show($miniGridId): ApiResource
    {
        return new ApiResource(array_values($this->generationAssetsService->getGenerationAssets($miniGridId)));
    }
}
