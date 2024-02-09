<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\MainSettings;
use App\Services\MainSettingsService;
use Illuminate\Http\Request;

class MainSettingsController extends Controller
{
    public function __construct(private MainSettingsService $mainSettingsService)
    {
    }

    public function index(): ApiResource
    {
        return ApiResource::make($this->mainSettingsService->getAll()->first());
    }

    public function update(MainSettings $mainSettings, Request $request): ApiResource
    {
        $mainSettingsData = $request->only([
            'site_title',
            'company_name',
            'currency',
            'country',
            'language',
            'vat_energy',
            'vat_appliance',
            'usage_type'
        ]);

        return ApiResource::make($this->mainSettingsService->update($mainSettings, $mainSettingsData));
    }
}
