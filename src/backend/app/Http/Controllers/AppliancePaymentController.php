<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\AssetPerson;
use App\Services\AppliancePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppliancePaymentController extends Controller {
    public function __construct(private AppliancePaymentService $appliancePaymentService) {}

    public function store(AssetPerson $appliancePerson, Request $request): ApiResource {
        try {
            DB::connection('tenant')->beginTransaction();
            $this->appliancePaymentService->getPaymentForAppliance($request, $appliancePerson);
            DB::connection('tenant')->commit();

            return ApiResource::make($appliancePerson);
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw new \Exception($e->getMessage());
        }
    }
}
