<?php

namespace App\Http\Controllers;

use App\Events\PaymentSuccessEvent;
use App\Events\TransactionSuccessfulEvent;
use App\Http\Requests\CreateAppliancePersonRequest;
use App\Http\Requests\UpdateAppliancePersonTotalCostRequest;
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
use App\Services\DeviceService;
use App\Services\PaymentInitiationService;
use App\Services\UserAppliancePersonService;
use App\Services\UserService;
use Dedoc\Scramble\Attributes\PathParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppliancePersonController extends Controller {
    public const CASH_TRANSACTION_PROVIVER = 0;

    public function __construct(
        private AppliancePerson $appliancePerson,
        private AppliancePersonService $appliancePersonService,
        private UserAppliancePersonService $userAppliancePersonService,
        private UserService $userService,
        private DeviceService $deviceService,
        private PaymentInitiationService $paymentInitiationService,
        private ApplianceRateService $applianceRateService,
    ) {}

    /**
     * Sell an appliance to a person.
     *
     * Creates a new AppliancePerson record linking the appliance to the person.
     *
     * For `installment` sales the installment rates are generated immediately, for `energy_service` sales no rates are created.
     * When a `device_serial` is given, that device is assigned to the person.
     * When a `down_payment` is given, it is processed as well:
     * cash payments (`payment_provider: 0`) are booked immediately,
     * online payments are initiated and the provider's initiation data (e.g. a payment page URL) is returned next to `appliance_person`.
     */
    #[PathParameter('appliance', description: 'ID of the appliance (the product) being sold.')]
    #[PathParameter('person', description: 'ID of the person (customer) buying the appliance.')]
    public function store(
        Appliance $appliance,
        Person $person,
        CreateAppliancePersonRequest $request,
    ): ApiResource {
        try {
            $user = $this->userService->getById($request->integer('user_id'));
            $paymentType = $request->input('payment_type') ?? AppliancePerson::PAYMENT_TYPE_INSTALLMENT;
            $downPayment = (float) $request->input('down_payment', 0);

            DB::connection('tenant')->beginTransaction();

            $appliancePerson = $this->createAppliancePerson($appliance, $person, $request, $user, $paymentType);

            if (!$appliancePerson->isEnergyService()) {
                $this->createInstallmentRates($appliancePerson, $appliance, $request->input('rate_type'));
            }

            if ($request->input('device_serial')) {
                $this->assignDevice($appliancePerson, $request);
            }

            $responseArray = $downPayment > 0
                ? $this->processDownPayment($appliancePerson, $downPayment, $request)
                : ['appliance_person' => $appliancePerson];

            DB::connection('tenant')->commit();

            return ApiResource::make($responseArray);
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw $e;
        }
    }

    private function createAppliancePerson(Appliance $appliance, Person $person, Request $request, ?User $user, string $paymentType): AppliancePerson {
        $isEnergyService = $paymentType === AppliancePerson::PAYMENT_TYPE_ENERGY_SERVICE;

        $appliancePerson = $this->appliancePersonService->make([
            'appliance_id' => $appliance->id,
            'person_id' => $person->id,
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
     * @return non-empty-array<string, mixed>
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
     * List sold appliances of a person.
     *
     * Returns all AppliancePerson records of the given person, including soft-deleted ones,
     * each with the sold appliance, its installment rates and its activity logs.
     */
    #[PathParameter('person', description: 'ID of the person (customer) whose sold appliances are listed.')]
    public function index(Person $person): ApiResource {
        return ApiResource::make($this->appliancePersonService->getSoldAppliancesForPerson($person->id));
    }

    /**
     * Get sold appliance details.
     *
     * Returns a single AppliancePerson record with the sold appliance, its installment rates,
     * its activity logs, the assigned device and the computed `totalPayments` and `totalRemainingAmount`.
     */
    #[PathParameter('appliancePersonId', description: 'ID of the AppliancePerson (sale) record — not the appliance ID.')]
    public function show(int $appliancePersonId): ApiResource {
        return ApiResource::make($this->appliancePersonService->getSoldApplianceDetails($appliancePersonId));
    }

    /**
     * List installment rates of a sold appliance.
     *
     * Returns the paginated installment rates of the given AppliancePerson record,
     * ordered by due date (oldest first), each with its activity logs.
     */
    #[PathParameter('appliancePersonId', description: 'ID of the AppliancePerson (sale) record — not the appliance ID.')]
    #[QueryParameter('per_page', description: 'Number of installment rates per page.', type: 'int', default: 15)]
    public function getRates(int $appliancePersonId, Request $request): ApiResource {
        $perPage = $request->integer('per_page', 15);
        $appliancePerson = $this->appliancePersonService->getById($appliancePersonId);

        return ApiResource::make($this->appliancePersonService->getRates($appliancePerson, $perPage));
    }

    /**
     * Update the total cost of a sold appliance.
     *
     * Sets a new total cost on the AppliancePerson record and redistributes the outstanding amount
     * across the unpaid installment rates.
     * When `rate_count` (and `rate_type`) are given, the unpaid rates are regenerated on a new schedule instead.
     * Returns the refreshed sold appliance details.
     */
    #[PathParameter('appliancePersonId', description: 'ID of the AppliancePerson (sale) record — not the appliance ID.')]
    public function updateTotalCost(int $appliancePersonId, UpdateAppliancePersonTotalCostRequest $request): ApiResource {
        $newTotalCost = $request->integer('new_total_cost');
        $creatorId = $request->integer('admin_id');
        $rateCount = $request->has('rate_count') ? $request->integer('rate_count') : null;
        $rateType = $request->input('rate_type');
        $appliancePerson = $this->appliancePerson::findOrFail($appliancePersonId);

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
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw $e;
        }

        return ApiResource::make(
            $this->appliancePersonService->getSoldApplianceDetails($appliancePersonId)
        );
    }

    /**
     * List activity logs of a sold appliance.
     *
     * Returns the paginated activity logs of the given AppliancePerson record, newest first.
     */
    #[PathParameter('appliancePersonId', description: 'ID of the AppliancePerson (sale) record — not the appliance ID.')]
    #[QueryParameter('per_page', description: 'Number of log entries per page.', type: 'int', default: 10)]
    public function getLogs(int $appliancePersonId, Request $request): ApiResource {
        $perPage = $request->integer('per_page', 10);
        $appliancePerson = $this->appliancePersonService->getById($appliancePersonId);

        return ApiResource::make($this->appliancePersonService->getLogs($appliancePerson, $perPage));
    }

    /**
     * Delete a sold appliance.
     *
     * Soft-deletes the AppliancePerson record, releases the assigned device (if any)
     * and writes an activity log entry.
     * Returns the details of the deleted record.
     */
    #[PathParameter('appliancePersonId', description: 'ID of the AppliancePerson (sale) record — not the appliance ID.')]
    #[QueryParameter('admin_id', description: 'ID of the MPM user performing the deletion; recorded in the activity log.', type: 'int')]
    public function destroy(int $appliancePersonId, Request $request): ApiResource {
        $creatorId = $request->integer('admin_id');
        $appliancePerson = $this->appliancePerson::findOrFail($appliancePersonId);

        try {
            DB::connection('tenant')->beginTransaction();
            $this->appliancePersonService->deleteWithDeviceRelease($appliancePerson, $creatorId);
            DB::connection('tenant')->commit();
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw $e;
        }

        return ApiResource::make(
            $this->appliancePersonService->getSoldApplianceDetails($appliancePersonId)
        );
    }
}
