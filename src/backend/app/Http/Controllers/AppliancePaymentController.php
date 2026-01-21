<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Models\AppliancePerson;
use App\Services\AppliancePaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppliancePaymentController extends Controller {
    public function __construct(private AppliancePaymentService $appliancePaymentService) {}

    public function store(AppliancePerson $appliancePerson, Request $request): ApiResource {
        try {
            DB::connection('tenant')->beginTransaction();
            $result = $this->appliancePaymentService->getPaymentForAppliance($request, $appliancePerson);
            DB::connection('tenant')->commit();

            return ApiResource::make([
                'appliance_person' => $result['appliance_person'],
                'transaction_id' => $result['transaction_id'],
            ]);
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function checkStatus(int $transactionId): ApiResource {
        $status = $this->appliancePaymentService->checkPaymentStatus($transactionId);

        return ApiResource::make($status);
    }
}
