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

            $applianceDetail = $this->appliancePersonService->getSoldApplianceDetails($appliancePerson->id);
            $result = $this->appliancePaymentService->initiateInstallmentPayment(
                $applianceDetail,
                $request->float('amount'),
                $request->integer('payment_provider', 0),
            );

            DB::connection('tenant')->commit();
        } catch (\InvalidArgumentException $e) {
            DB::connection('tenant')->rollBack();

            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw $e;
        }

        if ($result['process_immediately']) {
            dispatch(new ProcessPayment($request->attributes->get('companyId'), $result['transaction']->id));
        }

        return ApiResource::make(array_merge(
            [
                'appliance_person' => $appliancePerson,
                'transaction_id' => $result['transaction']->id,
            ],
            $result['provider_data'],
        ));
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
}
