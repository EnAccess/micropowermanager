<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Services\MiniGridSolarService;
use Illuminate\Http\Request;

class JetsonMiniGridSolarController extends controller
{

    public function __construct(private MiniGridSolarService $miniGridSolarService)
    {
    }

    public function show($miniGridId,Request $request):ApiResource
    {
        $limit = $request->get('limit');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $weatherData = $request->input('weather');

        return  ApiResource::make($this->miniGridSolarService->getForJetsonById($miniGridId,$startDate,$endDate,$limit,$weatherData));
    }


}