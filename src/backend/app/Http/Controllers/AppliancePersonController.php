<?php

namespace App\Http\Controllers;

use App\Events\PaymentSuccessEvent;
use App\Events\TransactionSuccessfulEvent;
use App\Http\Resources\ApiResource;
use App\Jobs\ProcessPayment;
use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\Person\Person;
use App\Models\Transaction\Transaction;
use App\Models\User;
use App\Services\AddressesService;
use App\Services\AddressGeographicalInformationService;
use App\Services\AppliancePersonService;
use App\Services\ApplianceRateService;
use App\Services\ApplianceService;
use App\Services\DeviceService;
use App\Services\GeographicalInformationService;
use App\Services\PaymentInitializationService;
use App\Services\UserAppliancePersonService;
use App\Services\UserService;
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
        private AddressesService $addressesService,
        private GeographicalInformationService $geographicalInformationService,
        private AddressGeographicalInformationService $addressGeographicalInformationService,
        private PaymentInitializationService $paymentInitializationService,
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
            throw new \Exception($e->getMessage(), (int) $e->getCode(), $e);
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
        $deviceSerial = $request->input('device_serial');
        $addressData = $request->input('address');
        $points = $request->input('points');

        $device = $this->deviceService->getBySerialNumber($deviceSerial);
        $this->deviceService->update($device, ['person_id' => $appliancePerson->person_id]);

        $address = $this->addressesService->make([
            'street' => $addressData['street'],
            'city_id' => $addressData['city_id'],
        ]);

        $this->addressesService->assignAddressToOwner($appliancePerson->person, $address);

        $geographicalInformation = $this->geographicalInformationService->make([
            'points' => $points,
        ]);
        $this->addressGeographicalInformationService->setAssigned($geographicalInformation);
        $this->addressGeographicalInformationService->setAssignee($address);
        $this->addressGeographicalInformationService->assign();
        $this->geographicalInformationService->save($geographicalInformation);
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

        $result = $this->paymentInitializationService->initialize(
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
                paymentType: 'down payment',
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
        $appliances = $this->appliancePerson::with('appliance.applianceType', 'rates.logs', 'logs.owner')
            ->where('person_id', $person->id)
            ->get();

        return ApiResource::make($appliances);
    }

    public function show(int $applianceId): ApiResource {
        $appliance = $this->appliancePersonService->getApplianceDetails($applianceId);

        return ApiResource::make($appliance);
    }

    public function getRates(int $appliancePersonId, Request $request): ApiResource {
        $perPage = $request->get('per_page', 15);

        $appliancePerson = $this->appliancePerson::findOrFail($appliancePersonId);

        return ApiResource::make($appliancePerson->rates()
            ->with('logs.owner')
            ->orderBy('due_date', 'asc')
            ->paginate($perPage));
    }

    public function getLogs(int $appliancePersonId, Request $request): ApiResource {
        $perPage = $request->get('per_page', 10);

        $appliancePerson = $this->appliancePerson::findOrFail($appliancePersonId);

        return ApiResource::make($appliancePerson->logs()
            ->with('owner')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage));
    }
}
