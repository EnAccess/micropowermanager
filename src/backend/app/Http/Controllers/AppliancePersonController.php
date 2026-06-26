<?php

namespace App\Http\Controllers;

use App\Events\PaymentSuccessEvent;
use App\Events\TransactionSuccessfulEvent;
use App\Http\Resources\ApiResource;
use App\Jobs\ProcessPayment;
use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\GeographicalInformation;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use App\Models\User;
use App\Services\AppliancePersonService;
use App\Services\ApplianceRateService;
use App\Services\ApplianceService;
use App\Services\DeviceService;
use App\Services\PaymentInitiationService;
use App\Services\UserAppliancePersonService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AppliancePersonController extends Controller {
    public const CASH_TRANSACTION_PROVIVER = 0;

    public function __construct(
        private AppliancePerson $appliancePerson,
        private AppliancePersonService $appliancePersonService,
        private UserAppliancePersonService $userAppliancePersonService,
        private UserService $userService,
        private DeviceService $deviceService,
        private PaymentInitiationService $paymentInitiationService,
        private ApplianceService $applianceService,
        private ApplianceRateService $applianceRateService,
    ) {}

    public function store(
        Appliance $appliance,
        Request $request,
    ): ApiResource {
        try {
            $user = $this->userService->getById($request->input('user_id'));
            $appliance = $this->applianceService->getById($request->input('id'));
            $paymentType = $request->input('payment_type', AppliancePerson::PAYMENT_TYPE_INSTALLMENT);
            $isEnergyService = $paymentType === AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE;
            $downPayment = (float) $request->input('down_payment', 0);
            $deviceSerial = $request->input('device_serial');

            DB::connection('tenant')->beginTransaction();

            $appliancePerson = $this->createAppliancePerson($request, $user, $paymentType);

            if (!$isEnergyService) {
                $this->createInstallmentRates($appliancePerson, $appliance, $request->input('rate_type'));
            }

            if ($deviceSerial) {
                $this->assignDevice($appliancePerson, $request);
            }

            $responseArray = ['appliance_person' => $appliancePerson];

            if ($downPayment > 0) {
                $responseArray = $this->processDownPayment($appliancePerson, $downPayment, $request);
            }

            DB::connection('tenant')->commit();

            return ApiResource::make($responseArray);
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    private function createAppliancePerson(Request $request, ?User $user, string $paymentType): AppliancePerson {
        $isEnergyService = $paymentType === AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE;

        $appliancePerson = $this->appliancePersonService->make([
            'appliance_id' => $request->input('id'),
            'person_id' => $request->input('person_id'),
            'total_cost' => $isEnergyService ? 0 : $request->integer('cost'),
            'rate_count' => $isEnergyService ? 0 : $request->integer('rate'),
            'down_payment' => (float) $request->input('down_payment', 0),
            'device_serial' => $request->input('device_serial'),
            'payment_type' => $paymentType,
            'minimum_payable_amount' => $isEnergyService ? $request->input('minimum_payable_amount') : null,
            'price_per_day' => $isEnergyService ? $request->input('price_per_day') : null,
        ]);

        $this->userAppliancePersonService->setAssigned($appliancePerson);
        $this->userAppliancePersonService->setAssignee($user);
        $this->userAppliancePersonService->assign();
        $this->appliancePersonService->save($appliancePerson);

        return $appliancePerson;
    }

    private function createInstallmentRates(AppliancePerson $appliancePerson, Appliance $appliance, string $installmentType): void {
        $cost = (int) $appliancePerson->total_cost;
        $preferredPrice = $appliance->price;

        if ($cost !== $preferredPrice) {
            $this->appliancePersonService->createLogForSoldAppliance($appliancePerson, $cost, $preferredPrice);
        }

        $this->applianceRateService->create($appliancePerson, $installmentType);
    }

    private function assignDevice(AppliancePerson $appliancePerson, Request $request): void {
        $device = $this->deviceService->getBySerialNumber($request->input('device_serial'));
        $this->deviceService->update($device, ['person_id' => $appliancePerson->person_id]);

        $this->deviceService->assignLocation($device, GeographicalInformation::pointFromString($request->input('points')));
    }

    /**
     * @return array<string, mixed>
     */
    private function processDownPayment(AppliancePerson $appliancePerson, float $downPayment, Request $request): array {
        $addressData = $request->input('address');
        $deviceSerial = $request->input('device_serial');
        $sender = isset($addressData) ? $addressData['phone'] : '-';
        $message = $deviceSerial ?? (string) $appliancePerson->id;
        $person = $appliancePerson->person;
        $paymentProviderId = (int) $request->input('payment_provider', 0);
        $companyId = $request->attributes->get('companyId');
        $downPaymentInitData = [];

        $result = $this->paymentInitiationService->initiate(
            providerId: $paymentProviderId,
            amount: $downPayment,
            sender: $sender,
            message: $message,
            type: Transaction::TYPE_DOWN_PAYMENT,
            customerId: $person->id,
            serialId: $deviceSerial ?? null,
        );

        if ($paymentProviderId === $this::CASH_TRANSACTION_PROVIVER) {
            $applianceRate = $this->applianceRateService->createPaidRate($appliancePerson, $downPayment);
            event(new PaymentSuccessEvent(
                amount: (int) $result['transaction']->amount,
                paymentService: 'web',
                paymentType: Transaction::TYPE_DOWN_PAYMENT,
                sender: $result['transaction']->sender,
                paidFor: $applianceRate,
                payer: $appliancePerson->person,
                transaction: $result['transaction'],
            ));
            event(new TransactionSuccessfulEvent($result['transaction']));
        } else {
            dispatch(new ProcessPayment($companyId, $result['transaction']->id));
            $downPaymentInitData = $result['provider_data'];
        }

        return array_merge(['appliance_person' => $appliancePerson], $downPaymentInitData);
    }

    /**
     * Display the specified resource.
     */
    public function index(Person $person, Request $request): ApiResource {
        $appliances = $this->appliancePerson::withTrashed()
            ->with('appliance.applianceType', 'rates.logs', 'logs.owner')
            ->where('person_id', $person->id)
            ->get();

        return ApiResource::make($appliances);
    }

    public function show(int $applianceId): ApiResource {
        $appliance = $this->appliancePersonService->getApplianceDetails($applianceId);

        return ApiResource::make($appliance);
    }

    public function getRates(int $appliancePersonId, Request $request): ApiResource {
        $perPage = $request->input('per_page', 15);

        $appliancePerson = $this->appliancePerson::withTrashed()->findOrFail($appliancePersonId);

        return ApiResource::make($appliancePerson->rates()
            ->with('logs.owner')
            ->oldest('due_date')
            ->paginate($perPage));
    }

    public function updateTotalCost(int $appliancePersonId, Request $request): ApiResource {
        $newTotalCost = $request->integer('new_total_cost');
        $creatorId = $request->integer('admin_id');
        $rateCount = $request->has('rate_count') ? $request->integer('rate_count') : null;
        $rateType = $request->input('rate_type');
        $appliancePerson = $this->appliancePerson::findOrFail($appliancePersonId);

        if ($rateType !== null && !in_array($rateType, ['monthly', 'weekly'], true)) {
            throw ValidationException::withMessages(['rate_type' => 'Rate type must be monthly or weekly']);
        }
        if ($rateCount !== null && $rateCount < 1) {
            throw ValidationException::withMessages(['rate_count' => 'Installment count must be at least 1']);
        }

        try {
            DB::connection('tenant')->beginTransaction();
            $this->applianceRateService->recomputeRatesFromTotalCost(
                $appliancePerson,
                $newTotalCost,
                $creatorId,
                $rateCount,
                $rateType,
            );
            DB::connection('tenant')->commit();
        } catch (ValidationException $e) {
            DB::connection('tenant')->rollBack();
            throw $e;
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        return ApiResource::make(
            $this->appliancePersonService->getApplianceDetails($appliancePersonId)
        );
    }

    public function getLogs(int $appliancePersonId, Request $request): ApiResource {
        $perPage = $request->input('per_page', 10);

        $appliancePerson = $this->appliancePerson::withTrashed()->findOrFail($appliancePersonId);

        return ApiResource::make($appliancePerson->logs()
            ->with('owner')->latest()
            ->paginate($perPage));
    }

    public function destroy(int $appliancePersonId, Request $request): ApiResource {
        $creatorId = $request->integer('admin_id');
        $appliancePerson = $this->appliancePerson::findOrFail($appliancePersonId);

        try {
            DB::connection('tenant')->beginTransaction();
            $this->appliancePersonService->deleteWithDeviceRelease($appliancePerson, $creatorId);
            DB::connection('tenant')->commit();
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }

        return ApiResource::make(
            $this->appliancePersonService->getApplianceDetails($appliancePersonId)
        );
    }
}
