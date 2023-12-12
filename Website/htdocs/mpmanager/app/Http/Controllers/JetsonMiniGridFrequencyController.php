<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\MiniGridFrequency;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class JetsonMiniGridFrequencyController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param  Request $request
     * @return ApiResource
     */
    public function store(Request $request): ApiResource
    {
        $gridData = $request->input('grid');

        $frequency = MiniGridFrequency::query()->make([
            'mini_grid_id' => $request->input('mini_grid_id'),
            'node_id' => $request->input('node_id'),
            'device_id' => $request->input('device_id'),
            'frequency' => str_replace(',', '.', $gridData['value']),
            'frequency_unit' => $gridData['unit'],
            'time_stamp' => date('Y-m-d H:i:s', strtotime($gridData['time_stamp'])),

            ]);
        $frequency->save();
        return ApiResource::make($frequency);
    }
}
