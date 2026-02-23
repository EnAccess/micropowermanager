<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Jobs\ProcessPayment;
use App\Models\AppliancePerson;
use App\Services\AppliancePaymentService;
use App\Services\AppliancePersonService;
use App\Services\CashTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppliancePaymentController extends Controller {
    public function __construct(
        private AppliancePaymentService $appliancePaymentService,
        private AppliancePersonService $appliancePersonService,
        private CashTransactionService $cashTransactionService,
    ) {}

    public function store(AppliancePerson $appliancePerson, Request $request): ApiResource {
        try {
            DB::connection('tenant')->beginTransaction();
            $result = $this->getPaymentForAppliance($request, $appliancePerson);
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

    /**
     * @return array<string, mixed>
     */
    public function getPaymentForAppliance(Request $request, AppliancePerson $appliancePerson): array {
        $creatorId = auth('api')->user()->id;
        $amount = (float) $request->input('amount');
        $applianceDetail = $this->appliancePersonService->getApplianceDetails($appliancePerson->id);
        $this->appliancePaymentService->validateAmount($applianceDetail, $amount);
        $deviceSerial = $applianceDetail->device_serial;
        $applianceOwner = $appliancePerson->person;
        $companyId = $request->attributes->get('companyId');

        if (!$applianceOwner) {
            throw new \InvalidArgumentException('Appliance owner not found');
        }

        $ownerAddress = $applianceOwner->addresses()->where('is_primary', 1)->first();
        $sender = $ownerAddress == null ? '-' : $ownerAddress->phone;
        $transaction =
            $this->cashTransactionService->createCashTransaction($creatorId, $amount, $sender, $deviceSerial, $appliancePerson->id);

        dispatch(new ProcessPayment($companyId, $transaction->id));

        return [
            'appliance_person' => $appliancePerson,
            'transaction_id' => $transaction->id,
        ];
    }
}
