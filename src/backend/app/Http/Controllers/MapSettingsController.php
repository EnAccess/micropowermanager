<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\MapSettings;

class MapSettingsController extends Controller {
    public function __construct() {}

    public function index(): ApiResource {
        return new ApiResource(MapSettings::all());
    }

    public function update(int $id): ApiResource {
        $mapSettings = MapSettings::query()
            ->updateOrCreate(
                ['id' => $id],
                [
                    'zoom' => request('zoom'),
                    'latitude' => request('latitude'),
                    'longitude' => request('longitude'),
                    'provider' => request('provider'),
                ]
            );

        return new ApiResource([$mapSettings->fresh()]);
    }

    // TODO: remove this method and associated route references when docs can be updated
    public function checkBingApiKey(string $key): ApiResource {
        return ApiResource::make(['authentication' => '']);
    }
}
