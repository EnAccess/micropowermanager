<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\TicketSettings;
use Illuminate\Http\Request;

class TicketSettingsController extends Controller
{
    public function __construct(private TicketSettings $ticketSettings)
    {
    }

    public function index(): ApiResource
    {
        return new ApiResource(TicketSettings::all());
    }

    public function update(Request $request): ApiResource
    {
        $ticketSettings = $this->ticketSettings::query()->updateOrCreate(
            [
                'id' => $request->input('id')
            ],
            [
                'name' => $request->input('name'),
                'api_token' => $request->input('api_token'),
                'api_url' => $request->input('api_url'),
                'api_key' => $request->input('api_key')
            ]
        );

        return ApiResource::make($ticketSettings->fresh());
    }
}
