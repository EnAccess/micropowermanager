<?php

namespace App\Http\Controllers;

use App\Enums\PaymentInitiationProvider;
use App\Http\Resources\ApiResource;
use App\Jobs\ProcessPayment;
use App\Models\AppliancePerson;
use App\Models\Transaction\Transaction;
use App\Services\AppliancePaymentService;
use App\Services\AppliancePersonService;
use App\Services\PaymentInitiationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Lets an external party (e.g. a USSD app run by another company) look up a customer's
 * payable devices by phone number and register a payment against one, identified by serial,
 * processed immediately like a cash payment. Authenticated via the 'api-key' guard; see
 * routes/api.php (nested under appliances/payment, excluded from that group's auth:api) and
 * ThirdPartyApiResolverService::EXTERNAL_TRANSACTIONS_API.
 */
class ExternalTransactionController extends Controller {
    public function __construct(
        private AppliancePaymentService $appliancePaymentService,
        private AppliancePersonService $appliancePersonService,
        private PaymentInitiationService $paymentInitiationService,
    ) {}

    /**
     * Register a transaction from an external party.
     *
     * `serial` identifies the device (appliance, meter, or SHS) the payment is for.
     * `external_reference` is the caller's own transaction ID; repeating the same
     * reference returns the original result instead of registering it again.
     */
    public function store(Request $request): ApiResource|JsonResponse {
        $request->validate([
            'serial' => ['required', 'string'],
            'amount' => ['required', 'numeric'],
            'external_reference' => ['required', 'string'],
        ]);

        $appliancePerson = $this->appliancePersonService->getBySerialNumber($request->string('serial')->toString());
        if ($appliancePerson === null) {
            return response()->json(['message' => 'No appliance found for the given serial'], 404);
        }

        try {
            DB::connection('tenant')->beginTransaction();

            $applianceDetail = $this->appliancePersonService->getSoldApplianceDetails($appliancePerson->id);
            $amount = $request->float('amount');
            $this->appliancePaymentService->validateAmount($applianceDetail, $amount);

            $applianceOwner = $appliancePerson->person;
            if (!$applianceOwner) {
                throw new \InvalidArgumentException('Appliance owner not found');
            }

            $ownerAddress = $applianceOwner->addresses()->where('is_primary', 1)->first();
            $sender = $ownerAddress === null ? '-' : $ownerAddress->phone;
            $deviceSerial = $applianceDetail->device_serial;

            $result = $this->paymentInitiationService->initiate(
                providerId: PaymentInitiationProvider::ThirdParty->value,
                amount: $amount,
                sender: $sender,
                message: $deviceSerial ?? (string) $appliancePerson->id,
                type: Transaction::TYPE_DEFERRED_PAYMENT,
                customerId: $applianceOwner->id,
                serialId: $deviceSerial,
                externalReference: $request->string('external_reference')->toString(),
            );

            if ($result['process_immediately']) {
                $companyId = (int) $request->attributes->get('companyId');
                dispatch(new ProcessPayment($companyId, $result['transaction']->id));
            }

            DB::connection('tenant')->commit();

            return ApiResource::make([
                'appliance_person' => $appliancePerson,
                'transaction_id' => $result['transaction']->id,
            ]);
        } catch (\InvalidArgumentException $e) {
            DB::connection('tenant')->rollBack();

            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw $e;
        }
    }

    /**
     * List the payable devices for a customer, by phone number.
     *
     * A phone number can belong to more than one customer, and a customer can have more than
     * one phone number; this returns the union of every payable device across every customer
     * matched by the given number. Use `serial` from the result to pay via `store()`.
     */
    public function devices(Request $request): ApiResource {
        $request->validate([
            'phone' => ['required', 'phone:INTERNATIONAL'],
        ]);

        $normalizedPhone = phone($request->input('phone'))->formatE164();
        $appliancePeople = $this->appliancePersonService->getPayableByPhone($normalizedPhone);

        return ApiResource::make($appliancePeople->map(fn (AppliancePerson $appliancePerson): array => [
            'serial' => $appliancePerson->device_serial,
            'owner_name' => trim($appliancePerson->person->name.' '.$appliancePerson->person->surname),
            'appliance_name' => $appliancePerson->appliance->name ?? null,
            'payment_type' => $appliancePerson->payment_type,
            'remaining_amount' => $appliancePerson->isEnergyService() ? null : $appliancePerson->rates->sum('remaining'),
            'minimum_payable_amount' => $appliancePerson->isEnergyService() ? $appliancePerson->minimum_payable_amount : null,
        ])->values());
    }
}
