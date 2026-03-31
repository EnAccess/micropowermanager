<?php

namespace App\Http\Controllers;

use App\Http\Resources\ApiResource;
use App\Jobs\ProcessPayment;
use App\Models\AppliancePerson;
use App\Services\AppliancePaymentService;
use App\Services\AppliancePersonService;
use App\Services\PaymentInitializationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppliancePaymentController extends Controller {
    public const CASH_TRANSACTION_PROVIVER = 0;

    public function __construct(
        private AppliancePaymentService $appliancePaymentService,
        private AppliancePersonService $appliancePersonService,
        private PaymentInitializationService $paymentInitializationService,
    ) {}

    public function store(AppliancePerson $appliancePerson, Request $request): ApiResource|JsonResponse {
        try {
            DB::connection('tenant')->beginTransaction();
            $result = $this->getPaymentForAppliance($request, $appliancePerson);
            DB::connection('tenant')->commit();

            return ApiResource::make(array_merge(
                [
                    'appliance_person' => $result['appliance_person'],
                    'transaction_id' => $result['transaction_id'],
                ],
                $result['provider_data'],
            ));
        } catch (\InvalidArgumentException $e) {
            DB::connection('tenant')->rollBack();

            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw new \Exception($e->getMessage(), (int) $e->getCode(), $e);
        }
    }

    public function checkStatus(int $transactionId): ApiResource {
        $status = $this->appliancePaymentService->checkPaymentStatus($transactionId);

        return ApiResource::make($status);
    }

    public function paymentProviders(): ApiResource {
        $providers = $this->paymentInitializationService->paymentProviders();

        return ApiResource::make($providers);
    }

    /**
     * @return array<string, mixed>
     */
    private function getPaymentForAppliance(Request $request, AppliancePerson $appliancePerson): array {
        $amount = (float) $request->input('amount');
        $providerId = (int) $request->input('payment_provider', 0);
        $companyId = $request->attributes->get('companyId');

        $applianceDetail = $this->appliancePersonService->getApplianceDetails($appliancePerson->id);
        $this->appliancePaymentService->validateAmount($applianceDetail, $amount);
        $deviceSerial = $applianceDetail->device_serial;
        $applianceOwner = $appliancePerson->person;

        if (!$applianceOwner) {
            throw new \InvalidArgumentException('Appliance owner not found');
        }

        $ownerAddress = $applianceOwner->addresses()->where('is_primary', 1)->first();
        $sender = $ownerAddress === null ? '-' : $ownerAddress->phone;

        $message = $deviceSerial ?? (string) $appliancePerson->id;

        $result = $this->paymentInitializationService->initialize(
            providerId: $providerId,
            amount: $amount,
            sender: $sender,
            message: $message,
            type: 'deferred_payment',
            customerId: $applianceOwner->id,
            serialId: $deviceSerial,
        );

        if ($providerId === $this::CASH_TRANSACTION_PROVIVER) {
            dispatch(new ProcessPayment($companyId, $result['transaction']->id));
        }

        return [
            'appliance_person' => $appliancePerson,
            'transaction_id' => $result['transaction']->id,
            'provider_data' => $result['provider_data'],
        ];
    }
}
