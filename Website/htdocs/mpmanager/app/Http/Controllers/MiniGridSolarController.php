<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MiniGridSolarService;

class MiniGridSolarController extends Controller
{
    public function __construct(private MiniGridSolarService $miniGridSolarService)
    {
    }

    public function show($miniGridId): \Illuminate\Http\JsonResponse|ApiResource
    {
        if ($reading = $this->miniGridSolarService->getById($miniGridId)) {
            return  ApiResource::make($reading);
        }

        return response()->setStatusCode(404)->json(['data' => 'Nothing found']);
    }
}
