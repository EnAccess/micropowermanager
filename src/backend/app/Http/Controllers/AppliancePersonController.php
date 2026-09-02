<?php

namespace App\Http\Controllers;

use App\Http\Requests\CreateAppliancePersonRequest;
use App\Http\Requests\UpdateAppliancePersonTotalCostRequest;
use App\Http\Resources\ApiResource;
use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\GeographicalInformation;
use App\Models\Person\Person;
use App\Models\User;
use App\Services\AppliancePersonService;
use App\Services\ApplianceRateService;
use App\Services\DeviceService;
use App\Services\UserAppliancePersonService;
use App\Services\UserService;
use Dedoc\Scramble\Attributes\PathParameter;
use Dedoc\Scramble\Attributes\QueryParameter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppliancePersonController extends Controller {
    public function __construct(
        private AppliancePerson $appliancePerson,
        private AppliancePersonService $appliancePersonService,
        private UserAppliancePersonService $userAppliancePersonService,
        private UserService $userService,
        private DeviceService $deviceService,
        private ApplianceRateService $applianceRateService,
    ) {}

    /**
     * Sell an appliance to a person.
     *
     * Creates a new AppliancePerson record linking the appliance to the person.
     *
     * For `installment` sales the installment rates are generated immediately, for `energy_service` sales no rates are created.
     * A `down_payment` on an `installment` sale becomes the first rate, due on the day of the sale;
     * it is paid like any other rate through the appliance payment endpoint.
     * When a `device_serial` is given, that device is assigned to the person.
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

            DB::connection('tenant')->beginTransaction();

            $appliancePerson = $this->createAppliancePerson($appliance, $person, $request, $user, $paymentType);

            if (!$appliancePerson->isEnergyService()) {
                $this->createInstallmentRates($appliancePerson, $appliance, $request->input('rate_type'));
            }

            if ($request->input('device_serial')) {
                $this->assignDevice($appliancePerson, $request);
            }

            DB::connection('tenant')->commit();

            return ApiResource::make(['appliance_person' => $appliancePerson]);
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

        if ($appliancePerson->down_payment > 0) {
            $this->applianceRateService->createDownPaymentRate($appliancePerson);
        }
    }

    private function assignDevice(AppliancePerson $appliancePerson, Request $request): void {
        $device = $this->deviceService->getBySerialNumber($request->input('device_serial'));
        $this->deviceService->update($device, ['person_id' => $appliancePerson->person_id]);

        $this->deviceService->assignLocation($device, GeographicalInformation::pointFromString($request->input('points')));
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
        $creatorId = auth('api')->user()->id;
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
    public function destroy(int $appliancePersonId): ApiResource {
        $creatorId = auth('api')->user()->id;
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
