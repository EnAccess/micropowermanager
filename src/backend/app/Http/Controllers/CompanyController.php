<?php

namespace App\Http\Controllers;

use App\Http\Requests\CompanyRegistrationRequest;
use App\Http\Resources\ApiResource;
use App\Services\CompanyRegistrationService;
use App\Services\CompanyService;
use App\Services\DatabaseProxyManagerService;
use Illuminate\Http\JsonResponse;

class CompanyController extends Controller {
    public function __construct(
        private CompanyService $companyService,
        private CompanyRegistrationService $companyRegistrationService,
        private DatabaseProxyManagerService $databaseProxyManagerService,
    ) {}

    public function store(CompanyRegistrationRequest $request): JsonResponse {
        $companyData = $request->only(['name', 'address', 'phone', 'email', 'country_id']);
        $adminData = $request->input('user');
        $plugins = $request->input('plugins');
        $usageType = $request->input('usage_type');

        $company = $this->companyRegistrationService->register(
            $companyData,
            $adminData,
            $plugins,
            $usageType
        );

        return response()->json([
            'message' => 'Congratulations! you have registered to MicroPowerManager successfully. You will be redirected to dashboard in seconds..',
            'company' => $company,
        ], 201);
    }

    public function get(string $email): ApiResource {
        $databaseProxy = $this->databaseProxyManagerService->findByEmail($email);

        return ApiResource::make($this->companyService->getByDatabaseProxy($databaseProxy));
    }
}
