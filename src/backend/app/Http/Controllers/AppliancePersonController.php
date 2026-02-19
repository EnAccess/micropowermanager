<?php

namespace App\Http\Controllers;

use App\Events\PaymentSuccessEvent;
use App\Http\Resources\ApiResource;
use App\Models\Appliance;
use App\Models\AppliancePerson;
use App\Models\Person\Person;
use App\Services\AddressesService;
use App\Services\AddressGeographicalInformationService;
use App\Services\AppliancePersonService;
use App\Services\ApplianceRateService;
use App\Services\ApplianceService;
use App\Services\CashTransactionService;
use App\Services\DeviceService;
use App\Services\GeographicalInformationService;
use App\Services\UserAppliancePersonService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AppliancePersonController extends Controller {
    public function __construct(
        private AppliancePerson $appliancePerson,
        private AppliancePersonService $appliancePersonService,
        private UserAppliancePersonService $userAppliancePersonService,
        private UserService $userService,
        private DeviceService $deviceService,
        private AddressesService $addressesService,
        private GeographicalInformationService $geographicalInformationService,
        private AddressGeographicalInformationService $addressGeographicalInformationService,
        private CashTransactionService $cashTransactionService,
        private ApplianceService $applianceService,
        private ApplianceRateService $applianceRateService,
    ) {}

    /**
     * Store a newly created resource in storage.
     */
    public function store(
        Appliance $appliance,
        Person $person,
        Request $request,
    ): ApiResource {
        try {
            $userId = $request->input('user_id');
            $applianceId = $request->input('id');
            $personId = $request->input('person_id');
            $cost = (int) $request->input('cost');
            $installmentCount = (int) $request->input('rate');
            $downPayment = (float) $request->input('down_payment');
            $deviceSerial = $request->input('device_serial');
            $addressData = $request->input('address');
            $user = $this->userService->getById($userId);
            $appliance = $this->applianceService->getById($applianceId);
            $installmentType = $request->input('rate_type');
            $points = $request->input('points');

            DB::connection('tenant')->beginTransaction();

            $appliancePerson = $this->appliancePersonService->make([
                'appliance_id' => $applianceId,
                'person_id' => $personId,
                'total_cost' => $cost,
                'rate_count' => $installmentCount,
                'down_payment' => $downPayment,
                'device_serial' => $deviceSerial,
            ]);
            $this->userAppliancePersonService->setAssigned($appliancePerson);
            $this->userAppliancePersonService->setAssignee($user);
            $this->userAppliancePersonService->assign();
            $this->appliancePersonService->save($appliancePerson);
            $preferredPrice = $appliance->price;
            if ($cost !== $preferredPrice) {
                $this->appliancePersonService->createLogForSoldAppliance($appliancePerson, $cost, $preferredPrice);
            }
            $this->applianceRateService->create($appliancePerson, $installmentType);

            if ($deviceSerial) {
                $device = $this->deviceService->getBySerialNumber($deviceSerial);
                $this->deviceService->update($device, ['person_id' => $personId]);
                $appliancePerson->device_serial = $deviceSerial;

                $address = $this->addressesService->make([
                    'street' => $addressData['street'],
                    'city_id' => $addressData['city_id'],
                ]);

                // Attach the new address to the person rather than the device.
                $this->addressesService->assignAddressToOwner($appliancePerson->person, $address);

                $geographicalInformation = $this->geographicalInformationService->make([
                    'points' => $points,
                ]);
                $this->addressGeographicalInformationService->setAssigned($geographicalInformation);
                $this->addressGeographicalInformationService->setAssignee($address);
                $this->addressGeographicalInformationService->assign();
                $this->geographicalInformationService->save($geographicalInformation);
            }
            if ($downPayment > 0) {
                $sender = isset($addressData) ? $addressData['phone'] : '-';
                $transaction = $this->cashTransactionService->createCashTransaction(
                    $user->id,
                    $downPayment,
                    $sender,
                    $deviceSerial
                );
                $applianceRate = $this->applianceRateService->getDownPaymentAsApplianceRate($appliancePerson);
                event(new PaymentSuccessEvent(
                    amount: (int) $transaction->amount,
                    paymentService: 'web',
                    paymentType: 'down payment',
                    sender: $transaction->sender,
                    paidFor: $applianceRate,
                    payer: $appliancePerson->person,
                    transaction: $transaction,
                ));
            }
            DB::connection('tenant')->commit();

            return ApiResource::make($appliancePerson);
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
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
