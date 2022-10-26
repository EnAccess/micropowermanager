<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MiniGridPVService;
use Illuminate\Http\Request;

class JetsonMiniGridPVController extends Controller
{
    public function __construct(private MiniGridPVService $miniGridPVService)
    {
    }

    public function show($miniGridId, Request $request)
    {
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        return ApiResource::make($this->miniGridPVService->getById($miniGridId, $startDate, $endDate));
    }
}
