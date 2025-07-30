<?php

namespace App\Http\Controllers;

use App\Events\PaymentSuccessEvent;
use App\Http\Resources\ApiResource;
use App\Models\Asset;
use App\Models\AssetPerson;
use App\Models\Person\Person;
use App\Services\AddressesService;
use App\Services\AddressGeographicalInformationService;
use App\Services\AppliancePersonService;
use App\Services\ApplianceRateService;
use App\Services\AssetService;
use App\Services\CashTransactionService;
use App\Services\GeographicalInformationService;
use App\Services\UserAppliancePersonService;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use MPM\Device\DeviceAddressService;
use MPM\Device\DeviceService;

class AssetPersonController extends Controller {
    public function __construct(
        private AssetPerson $assetPerson,
        private AppliancePersonService $assetPersonService,
        private UserAppliancePersonService $userAppliancePersonService,
        private UserService $userService,
        private DeviceService $deviceService,
        private AddressesService $addressesService,
        private DeviceAddressService $deviceAddressService,
        private GeographicalInformationService $geographicalInformationService,
        private AddressGeographicalInformationService $addressGeographicalInformationService,
        private CashTransactionService $cashTransactionService,
        private AssetService $applianceService,
        private ApplianceRateService $applianceRateService,
    ) {}

    /**
     * Store a newly created resource in storage.
     *
     * @param Asset   $asset
     * @param Person  $person
     * @param Request $request
     *
     * @return ApiResource
     */
    public function store(
        Asset $asset,
        Person $person,
        Request $request,
    ): ApiResource {
        try {
            $userId = $request->input('user_id');
            $applianceId = $request->input('id');
            $personId = $request->input('person_id');
            $cost = (float) $request->input('cost');
            $installmentCount = (int) $request->input('rate');
            $downPayment = (float) $request->input('down_payment');
            $deviceSerial = $request->input('device_serial');
            $addressData = $request->input('address');
            $user = $this->userService->getById($userId);
            $appliance = $this->applianceService->getById($applianceId);
            $installmentType = $request->input('rate_type');
            $points = $request->input('points');

            DB::connection('tenant')->beginTransaction();

            $appliancePerson = $this->assetPersonService->make([
                'asset_id' => $applianceId,
                'person_id' => $personId,
                'total_cost' => $cost,
                'rate_count' => $installmentCount,
                'down_payment' => $downPayment,
                'device_serial' => $deviceSerial,
            ]);
            $this->userAppliancePersonService->setAssigned($appliancePerson);
            $this->userAppliancePersonService->setAssignee($user);
            $this->userAppliancePersonService->assign();
            $this->assetPersonService->save($appliancePerson);
            $preferredPrice = $appliance->price;
            if ($cost !== $preferredPrice) {
                $this->assetPersonService->createLogForSoldAppliance($appliancePerson, $cost, $preferredPrice);
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
                $this->deviceAddressService->setAssigned($address);
                $this->deviceAddressService->setAssignee($device);
                $this->deviceAddressService->assign();
                $this->addressesService->save($address);

                $geographicalInformation = $this->geographicalInformationService->make([
                    'points' => $points,
                ]);
                $this->addressGeographicalInformationService->setAssigned($geographicalInformation);
                $this->addressGeographicalInformationService->setAssignee($address);
                $this->addressGeographicalInformationService->assign();
                $this->geographicalInformationService->save($geographicalInformation);
            }
            if ($downPayment > 0) {
                $sender = !isset($addressData) ? '-' : $addressData['phone'];
                $transaction = $this->cashTransactionService->createCashTransaction(
                    $user->id,
                    $downPayment,
                    $sender,
                    $deviceSerial
                );
                $applianceRate = $this->applianceRateService->getDownPaymentAsAssetRate($appliancePerson);
                event(new PaymentSuccessEvent(
                    amount: $transaction->amount,
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
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param Person  $person
     * @param Request $request
     *
     * @return ApiResource
     */
    public function index(Person $person, Request $request): ApiResource {
        $assets = $this->assetPerson::with('asset.assetType', 'rates.logs', 'logs.owner')
            ->where('person_id', $person->id)
            ->get();

        return ApiResource::make($assets);
    }

    public function show(int $applianceId): ApiResource {
        $appliance = $this->assetPersonService->getApplianceDetails($applianceId);

        return ApiResource::make($appliance);
    }
}
