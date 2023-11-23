<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\TicketSettings;

class TicketSettingsController extends Controller
{
    /**
     * @TicketSettings
     */

    private $ticketSettings;

    public function __construct(TicketSettings $ticketSettings)
    {
        $this->ticketSettings = $ticketSettings;
    }

    public function index(): ApiResource
    {
        return new ApiResource(TicketSettings::all());
    }

    public function update(TicketSettings $ticketSettings): ApiResource
    {
        $ticketSettings = TicketSettings::updateOrCreate(
            [
                'id' => request('id')
            ],
            [
                'name' => request('name'),
                'api_token' => request('api_token'),
                'api_url' => request('api_url'),
                'api_key' => request('api_key')
            ]
        );

        return new ApiResource($ticketSettings->fresh());
    }
}
