<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidBingApiKeyException;
use App\Http\Resources\ApiResource;
use App\Services\BingMapApiService;
use App\Models\MapSettings;

class MapSettingsController extends Controller
{

    private $bingMapApiService;

    public function __construct(BingMapApiService $bingMapApiService)
    {
        $this->bingMapApiService = $bingMapApiService;
    }

    public function index(): ApiResource
    {
        return new ApiResource(MapSettings::all());
    }

    public function update($id): ApiResource
    {
        $mapSettings = MapSettings::query()
            ->updateOrCreate(
                ['id' => $id],
                [
                    'zoom' => request('zoom'),
                    'latitude' => request('latitude'),
                    'longitude' => request('longitude'),
                    'provider' => request('provider'),
                    'bingMapApiKey' => request('bingMapApiKey')
                ]
            );
        return new ApiResource([$mapSettings->fresh()]);
    }

    public function checkBingApiKey($key): ApiResource
    {
        try {
            $apiAuthentication = $this->bingMapApiService->checkAuthenticationKey($key);
        } catch (InvalidBingApiKeyException $exception) {
            $apiAuthentication = false;
        }
        return ApiResource::make(['authentication' => $apiAuthentication]);
    }
}
