<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\MainSettings;

class MainSettingsController extends Controller
{
    /**
     * @MainSettings
     */

    private $mainSettings;

    public function __construct(MainSettings $mainSettings)
    {
        $this->mainSettings = $mainSettings;
    }

    public function index(): ApiResource
    {
        return new ApiResource(MainSettings::all());
    }

    public function update(MainSettings $mainSettings): ApiResource
    {
        $mainSettings = MainSettings::updateOrCreate(
            [
                'id' => request('id')
            ],
            [   'site_title' => request('site_title'),
                'company_name' => request('company_name'),
                'currency' => request('currency'),
                'country' => request('country'),
                'language' => request('language'),
                'vat_energy' => request('vat_energy'),
                'vat_appliance' => request('vat_appliance')
            ]
        );
        return new ApiResource([$mainSettings->fresh()]);
    }
}
