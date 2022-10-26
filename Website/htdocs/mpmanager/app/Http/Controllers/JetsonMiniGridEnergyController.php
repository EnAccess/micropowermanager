<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MiniGridEnergyService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class JetsonMiniGridEnergyController extends Controller
{
    public function __construct(private MiniGridEnergyService $miniGridEnergyService)
    {
    }

    public function show($miniGridId, Request $request): ApiResource
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        return  ApiResource::make($this->miniGridEnergyService->getById($miniGridId, $startDate, $endDate));
    }
}
