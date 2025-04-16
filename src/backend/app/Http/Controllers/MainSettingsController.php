<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\MainSettings;
use App\Services\CompanyService;
use App\Services\MainSettingsService;
use App\Services\UserService;
use Illuminate\Http\Request;

class MainSettingsController extends Controller {
    public function __construct(
        private MainSettingsService $mainSettingsService, private UserService $userService, private CompanyService $companyService,
    ) {}

    public function index(): ApiResource {
        return ApiResource::make($this->mainSettingsService->getAll()->first());
    }

    public function update(MainSettings $mainSettings, Request $request): ApiResource {
        $mainSettingsData = $request->only([
            'site_title',
            'company_name',
            'currency',
            'country',
            'language',
            'vat_energy',
            'vat_appliance',
            'usage_type',
        ]);

        $protectedPagePassword = $request->input('protected_page_password');
        if ($protectedPagePassword) {
            $mainSettingsData['protected_page_password'] = $protectedPagePassword;
        }

        return ApiResource::make($this->mainSettingsService->update($mainSettings, $mainSettingsData));
    }
}
