<?php

namespace App\Http\Controllers;

use App\Enums\PaymentInitiationProvider;
use App\Http\Resources\ApiResource;
use App\Http\Resources\PaymentProviderResource;
use App\Http\Resources\PaymentStatusResource;
use App\Jobs\ProcessPayment;
use App\Models\AppliancePerson;
use App\Models\Transaction\Transaction;
use App\Services\AppliancePaymentService;
use App\Services\AppliancePersonService;
use App\Services\PaymentInitiationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AppliancePaymentController extends Controller {
    public function __construct(
        private AppliancePaymentService $appliancePaymentService,
        private AppliancePersonService $appliancePersonService,
        private PaymentInitiationService $paymentInitiationService,
    ) {}

    /**
     * Create a payment for an appliance.
     *
     * Records a payment towards the installment plan of an appliance sold to a customer.
     * `payment_provider` is one of the IDs returned by the payment providers endpoint;
     * omit it (or send `0`) to record a cash payment.
     */
    public function store(AppliancePerson $appliancePerson, Request $request): ApiResource|JsonResponse {
        $request->validate([
            'amount' => ['required', 'numeric'],
            'payment_provider' => ['sometimes', 'integer', Rule::enum(PaymentInitiationProvider::class)],
        ]);

        try {
            DB::connection('tenant')->beginTransaction();
            $result = $this->makePaymentForAppliance(
                $request->float('amount'),
                $appliancePerson,
                $request->integer('payment_provider', 0),
                $companyId = $request->attributes->get('companyId')
            );

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
            throw $e;
        }
    }

    /**
     * Check the status of a payment.
     *
     * Poll this with the `transaction_id` returned when creating a payment
     * to see whether the transaction has been processed.
     */
    public function checkStatus(Transaction $transaction): PaymentStatusResource {
        $status = $this->appliancePaymentService->checkPaymentStatus($transaction);

        return PaymentStatusResource::make($status);
    }

    /**
     * List enabled payment providers.
     *
     * Returns the payment provider plugins that are _both_
     *  - enabled for the tenant
     *  - support initiating a payment
     * Use a provider's `id` as the `payment_provider` value when
     * creating an appliance payment.
     * Cash payments (ID `0`) are always available and not part of this list.
     */
    public function paymentProviders(): AnonymousResourceCollection {
        $providers = $this->paymentInitiationService->paymentProviders();

        return PaymentProviderResource::collection($providers);
    }

    /**
     * @return array{appliance_person: AppliancePerson, transaction_id: int, provider_data: array<string, mixed>}
     */
    private function makePaymentForAppliance(float $amount, AppliancePerson $appliancePerson, int $providerId, int $companyId): array {
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

        $result = $this->paymentInitiationService->initiate(
            providerId: $providerId,
            amount: $amount,
            sender: $sender,
            message: $message,
            type: Transaction::TYPE_DEFERRED_PAYMENT,
            customerId: $applianceOwner->id,
            serialId: $deviceSerial,
        );

        if ($result['process_immediately']) {
            dispatch(new ProcessPayment($companyId, $result['transaction']->id));
        }

        return [
            'appliance_person' => $appliancePerson,
            'transaction_id' => $result['transaction']->id,
            'provider_data' => $result['provider_data'],
        ];
    }
}
