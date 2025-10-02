<?php

namespace Inensus\SparkMeter\Services;

use App\Models\Address\Address;
use App\Models\ConnectionGroup;
use App\Models\ConnectionType;
use App\Models\GeographicalInformation;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use App\Services\AddressesService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inensus\SparkMeter\Exceptions\SparkAPIResponseException;
use Inensus\SparkMeter\Helpers\SmTableEncryption;
use Inensus\SparkMeter\Http\Requests\SparkMeterApiRequests;
use Inensus\SparkMeter\Models\SmCustomer;
use Inensus\SparkMeter\Models\SmMeterModel;
use Inensus\SparkMeter\Models\SmSite;
use Inensus\SparkMeter\Models\SmTariff;
use Inensus\SparkMeter\Models\SyncStatus;

class CustomerService implements ISynchronizeService {
    private string $rootUrl = '/customer/';

    public function __construct(
        private SparkMeterApiRequests $sparkMeterApiRequests,
        private SmTableEncryption $smTableEncryption,
        private Person $person,
        private SmCustomer $smCustomer,
        private SmMeterModel $smMeterModel,
        private SmTariff $smTariff,
        private SmSite $smSite,
        private Meter $meter,
        private Manufacturer $manufacturer,
        private ConnectionType $connectionType,
        private ConnectionGroup $connectionGroup,
        private SmSyncSettingService $smSyncSettingService,
        private SmSyncActionService $smSyncActionService,
        private SmSmsNotifiedCustomerService $smSmsNotifiedCustomerService,
    ) {}

    public function createCustomer($meterInfo, $siteId): void {
        $params = [
            'meter_serial' => $meterInfo->meter->serial_number,
        ];
        $sparkCustomersResult = $this->sparkMeterApiRequests->getByParams('/customers', $params, $siteId);
        if ($sparkCustomersResult['status'] === 'failure') {
            $customerExists = false;
        } else {
            $smCustomerHash = $this->hashCustomerWithMeterSerial($meterInfo->meter->serial_number, $siteId);
            $customerExists = true;
            $this->smCustomer->newQuery()->create([
                'customer_id' => $sparkCustomersResult['customers'][0]['id'],
                'mpm_customer_id' => $meterInfo->owner->id,
                'site_id' => $siteId,
                'credit_balance' => $sparkCustomersResult['credit_balance'],
                'hash' => $smCustomerHash,
            ]);
        }
        if (!$customerExists) {
            $rootUrl = '/system-info';
            $result = $this->sparkMeterApiRequests->get($rootUrl, $siteId);
            $grid = $result['grids'][0];

            $postParams = [
                'serial' => $meterInfo->meter->serial_number,
                'ground_serial' => $grid['serial'],
                'meter_tariff_name' => $meterInfo->tariff->name,
                'name' => $meterInfo->owner->name.' '.$meterInfo->owner->surname,
                'code' => strval($meterInfo->owner->id),
                'phone_number' => $meterInfo->owner->addresses[0]->phone,
                'operating_mode' => 'on',
                'starting_credit_balance' => '0',
            ];
            $result = $this->sparkMeterApiRequests->post($this->rootUrl, $postParams, $siteId);

            $smCustomerHash = $this->hashCustomerWithMeterSerial($meterInfo->meter->serial_number, $siteId);
            $this->smCustomer->newQuery()->create([
                'customer_id' => $result['customer_id'],
                'mpm_customer_id' => $meterInfo->owner->id,
                'site_id' => $siteId,
                'credit_balance' => $sparkCustomersResult['credit_balance'],
                'hash' => $smCustomerHash,
            ]);
        }
    }

    public function getSmCustomers($request) {
        $perPage = $request->input('per_page') ?? 15;

        return $this->smCustomer->newQuery()->with(['mpmPerson', 'site.mpmMiniGrid'])->paginate($perPage);
    }

    public function getSmCustomersCount(): int {
        return count($this->smCustomer->newQuery()->get());
    }

    public function getSmCustomerByCustomerId($customerId) {
        return $this->smCustomer->newQuery()->with([
            'mpmPerson.devices.device',
            'mpmPerson.addresses',
        ])->where('customer_id', $customerId)->first();
    }

    public function updateSparkCustomerInfo(array $customerData, $siteId) {
        try {
            $customerId = $customerData['id'];
            $putParams = [
                'active' => $customerData['active'],
                'meter_tariff_name' => $customerData['meter_tariff_name'],
                'name' => $customerData['name'],
                'coords' => $customerData['coords'],
                'address' => $customerData['address'],
            ];
            if ($customerData['phone_number']) {
                $putParams['phone_number'] = $customerData['phone_number'];
            }
            $sparkCustomerId = $this->sparkMeterApiRequests->put('/customers/'.$customerId, $putParams, $siteId);

            return $sparkCustomerId['customer_id'];
        } catch (\Exception $e) {
            Log::critical('updating customer info from spark api failed.', ['Error :' => $e->getMessage()]);
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function createRelatedPerson(array $customer, $site_id) {
        try {
            DB::connection('tenant')->beginTransaction();
            $sparkCustomerMeterSerial = $customer['meters'][0]['serial'];
            $meter = $this->meter->newQuery()->where('serial_number', $sparkCustomerMeterSerial)->first();
            $person = null;
            if ($meter === null) {
                $meter = new Meter();
                $geoLocation = new GeographicalInformation();
            } else {
                $geoLocation = $meter->device->person->addresses()->first()->geo()->first();
                if ($geoLocation === null) {
                    $geoLocation = new GeographicalInformation();
                }
                $person = $this->person->newQuery()->whereHas('devices.device', static function ($q) use ($meter) {
                    return $q->where('id', $meter->id);
                })->first();
            }
            if ($person === null) {
                $data = [
                    'name' => ($customer['name']) ? ($customer['name']) : '',
                    'phone' => ($customer['phone_number']) ? ($customer['phone_number']) : null,
                    'street1' => ($customer['meters'][0]['street1']) ? ($customer['meters'][0]['street1']) : null,
                ];
                $person = $this->createPerson($data);
            }
            $meter->serial_number = $sparkCustomerMeterSerial;
            $manufacturer = $this->manufacturer->newQuery()->where('name', 'Spark Meters')->firstOrFail();
            $meter->manufacturer()->associate($manufacturer);
            $meterModelName = explode('-', $customer['meters'][0]['serial'])[0];
            $smModel = $this->smMeterModel->newQuery()->with('meterType')->where(
                'model_name',
                $meterModelName
            )->firstOrFail();
            $meter->meterType()->associate($smModel->meterType);
            $meter->updated_at = date('Y-m-d h:i:s');
            $meter->save();

            $geoLocation->points = $customer['meters'][0]['coords'];
            $connectionType = $this->connectionType->newQuery()->first();
            $connectionGroup = $this->connectionGroup->newQuery()->first();

            $meter->device()->save($person);
            $currentTariffName = $customer['meters'][0]['current_tariff_name'];

            $smTariff = $this->smTariff->newQuery()->with('mpmTariff')->whereHas(
                'mpmTariff',
                function ($q) use ($currentTariffName) {
                    return $q->where('name', $currentTariffName);
                }
            )->first();
            if ($smTariff) {
                $meter->tariff()->associate($smTariff->mpmTariff);
            }
            $meter->save();
            if ($geoLocation->points == null) {
                $geoLocation->points = config('spark.geoLocation');
            }
            $meter->device->person->addresses()->first()->geo()->save($geoLocation);

            $site = $this->smSite->newQuery()->with('mpmMiniGrid')->where('site_id', $site_id)->firstOrFail();

            $sparkCity = $site->mpmMiniGrid->cities[0];

            $address = new Address();
            $address = $address->newQuery()->create([
                'city_id' => request()->input('city_id') ?? $sparkCity->id,
            ]);
            $address->owner()->associate($meter);
            $address->geo()->save($meter->device->address->geo()->first());
            $address->save();
            DB::connection('tenant')->commit();

            return $person->id;
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::critical('Error while synchronizing spark customers', ['message' => $e->getMessage()]);
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function createPerson(array $data) {
        $person = $this->person->newQuery()->create([
            'name' => $data['name'],
            'is_customer' => 1,
        ]);
        $addressService = App::make(AddressesService::class);

        $addressParams = [
            'phone' => $data['phone'],
            'street' => $data['street1'],
            'is_primary' => 1,
        ];
        $address = $addressService->instantiate($addressParams);
        $addressService->assignAddressToOwner($person, $address);

        return $person;
    }

    public function updateRelatedPerson(array $customer, $person, $site_id): void {
        $sparkCustomerMeterSerial = $customer['meters'][0]['serial'];
        $currentTariffName = $customer['meters'][0]['current_tariff_name'];
        $site = $this->smSite->newQuery()->with('mpmMiniGrid')->where('site_id', $site_id)->firstOrFail();
        $sparkCity = $site->mpmMiniGrid->cities[0];
        $address = $person->addresses()->where('is_primary', 1)->first();
        $address->update([
            'phone' => $customer['phone_number'],
            'street' => $customer['meters'][0]['street1'],
        ]);
        $meter = $person->meters()->first();
        $meter->device()->address()->update([
            'city_id' => $sparkCity->id,
        ]);
        if ($meter) {
            $meter->update([
                'serial_number' => $sparkCustomerMeterSerial,
            ]);
        }
        $smTariff = $this->smTariff->newQuery()->with(['mpmTariff'])->whereHas(
            'mpmTariff',
            function ($q) use ($currentTariffName) {
                return $q->where('name', $currentTariffName);
            }
        )->first();

        if ($smTariff) {
            $meter->tariff()->associate($smTariff->mpmTariff);
            $meter->save();
        }
        $geo = $meter->device->person->addresses()->first()->geo()->first();
        if ($geo && array_key_exists('coords', $customer['meters'][0])) {
            $geo->points = $customer['meters'][0]['coords'] === '' ?
                config('spark.geoLocation') : $customer['meters'][0]['coords'];
            $geo->update();
            $meter->device()->address()->update([
                'geo_id' => $geo->id,
            ]);
        }
        $person->update([
            'name' => $customer['name'],
            'surname' => '',
            'updated_at' => date('Y-m-d h:i:s'),
        ]);
    }

    public function checkConnectionAvailability(): array {
        $connectionType = $this->connectionType->newQuery()->first();

        $connectionGroup = $this->connectionGroup->newQuery()->first();

        $result = ['type' => false, 'group' => false];
        if ($connectionType) {
            $result['type'] = true;
        }
        if ($connectionGroup) {
            $result['group'] = true;
        }

        return $result;
    }

    public function hashCustomerWithMeterSerial($meterSerial, $siteId): string {
        try {
            $params = [
                'meter_serial' => $meterSerial,
            ];
            $sparkCustomersResult = $this->sparkMeterApiRequests->getByParams('/customers', $params, $siteId);
            $phone = $sparkCustomersResult['phone_number'] == null ? 'NA' : $sparkCustomersResult['phone_number'];

            return $this->modelHasher($sparkCustomersResult, $phone);
        } catch (GuzzleException $e) {
            throw new SparkAPIResponseException($e->getMessage());
        }
    }

    public function updateCustomerLowBalanceLimit($customerId, array $data) {
        $customer = $this->smCustomer->newQuery()->find($customerId);
        $customer->update([
            'low_balance_limit' => $data['low_balance_limit'],
        ]);

        return $customer->fresh();
    }

    public function searchCustomer($searchTerm, $paginate) {
        if ($paginate === 1) {
            return $this->smCustomer->newQuery()->with(['mpmPerson', 'site.mpmMiniGrid'])
                ->WhereHas('site.mpmMiniGrid', function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', '%'.$searchTerm.'%');
                })->orWhereHas('mpmPerson', function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', '%'.$searchTerm.'%');
                })->paginate(15);
        }

        return $this->smCustomer->newQuery()->with(['mpmPerson', 'site.mpmMiniGrid'])
            ->WhereHas('site.mpmMiniGrid', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%'.$searchTerm.'%');
            })->orWhereHas('mpmPerson', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%'.$searchTerm.'%');
            })->get();
    }

    public function getLowBalancedCustomers() {
        return $this->smCustomer->newQuery()->with([
            'mpmPerson.addresses' => function ($q) {
                return $q->where('is_primary', 1);
            },
        ])
            ->whereNotNull('low_balance_limit')
            ->where('low_balance_limit', '>', 0)
            ->where('low_balance_limit', '>', 'credit_balance')->get();
    }

    public function sync() {
        $synSetting = $this->smSyncSettingService->getSyncSettingsByActionName('Customers');
        $syncAction = $this->smSyncActionService->getSyncActionBySynSettingId($synSetting->id);
        try {
            $syncCheck = $this->syncCheck(true);
            $customersCollection = collect($syncCheck)->except('available_site_count');
            $customersCollection->each(function (array $customers) {
                $customers['site_data']->filter(function (array $customer): bool {
                    return $customer['syncStatus'] === SyncStatus::NOT_REGISTERED_YET;
                })->each(function (array $customer) use ($customers) {
                    $mpmCustomerId = $this->createRelatedPerson($customer, $customers['site_id']);
                    $this->smCustomer->newQuery()->create([
                        'customer_id' => $customer['id'],
                        'mpm_customer_id' => $mpmCustomerId,
                        'site_id' => $customers['site_id'],
                        'credit_balance' => $customer['credit_balance'],
                        'hash' => $customer['hash'],
                    ]);
                });
                $customers['site_data']->filter(function (array $customer): bool {
                    return $customer['syncStatus'] === SyncStatus::MODIFIED;
                })->each(function (array $customer) use ($customers) {
                    is_null($customer['relatedPerson']) ? $this->createRelatedPerson(
                        $customer,
                        $customers['site_id']
                    ) : $this->updateRelatedPerson(
                        $customer,
                        $customer['relatedPerson'],
                        $customers['site_id']
                    );

                    $customer['registeredSparkCustomer']->update([
                        'hash' => $customer['hash'],
                        'site_id' => $customers['site_id'],
                        'credit_balance' => $customer['credit_balance'],
                    ]);
                    $this->smSmsNotifiedCustomerService
                        ->removeLowBalancedCustomer($customer['registeredSparkCustomer']);
                });
            });
            $this->smSyncActionService->updateSyncAction($syncAction, $synSetting, true);

            return $this->smCustomer->newQuery()->with([
                'mpmPerson',
                'site.mpmMiniGrid',
            ])->paginate(config('spark.paginate'));
        } catch (\Exception $e) {
            $this->smSyncActionService->updateSyncAction($syncAction, $synSetting, false);
            Log::critical('Spark customers sync failed.', ['Error :' => $e->getMessage()]);
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return mixed[]
     */
    public function syncCheck($returnData = false): array {
        $returnArray = ['available_site_count' => 0];
        $sites = $this->smSite->newQuery()->where('is_authenticated', 1)->where('is_online', 1)->get();
        foreach ($sites as $key => $site) {
            $returnArray['available_site_count'] = $key + 1;
            try {
                $sparkCustomers = $this->sparkMeterApiRequests->get('/customers', $site->site_id);
            } catch (SparkAPIResponseException $e) {
                Log::critical('Spark meter customers sync-check failed.', ['Error :' => $e->getMessage()]);
                if ($returnData) {
                    $returnArray[] = ['result' => false];
                }
                throw new \Exception($e->getMessage(), $e->getCode(), $e);
            }

            $sparkCustomersCollection = collect($sparkCustomers['customers'])->filter(function (array $customer): bool {
                return $customer['id'] && $customer['meters'][0]['current_tariff_name'];
            });
            $sparkCustomers = $this->smCustomer->newQuery()->where('site_id', $site->site_id)->get();
            $people = $this->person->newQuery()->get();

            $sparkCustomersCollection->transform(function (array $customer) use ($sparkCustomers, $people): array {
                $registeredSparkCustomer = $sparkCustomers->firstWhere('customer_id', $customer['id']);
                $relatedPerson = null;
                $phone = $customer['phone_number'] == null ? 'NA' : $customer['phone_number'];
                $customerHash = $this->modelHasher($customer, $phone);
                if ($registeredSparkCustomer) {
                    $customer['syncStatus'] = $customerHash === $registeredSparkCustomer->hash ?
                        SyncStatus::SYNCED : SyncStatus::MODIFIED;
                    $relatedPerson = $people->find($registeredSparkCustomer->mpm_customer_id);
                } else {
                    $customer['syncStatus'] = SyncStatus::NOT_REGISTERED_YET;
                }
                $customer['hash'] = $customerHash;
                $customer['relatedPerson'] = $relatedPerson;
                $customer['registeredSparkCustomer'] = $registeredSparkCustomer;

                return $customer;
            });

            $customerSyncStatus = $sparkCustomersCollection->whereNotIn('syncStatus', [1])->count();

            if ($customerSyncStatus) {
                $returnData ? array_push($returnArray, [
                    'site_id' => $site->site_id,
                    'site_data' => $sparkCustomersCollection,
                    'result' => false,
                ]) : array_push($returnArray, ['result' => false]);
            } else {
                $returnData ? array_push($returnArray, [
                    'site_id' => $site->site_id,
                    'site_data' => $sparkCustomersCollection,
                    'result' => true,
                ]) : array_push($returnArray, ['result' => true]);
            }
        }

        return $returnArray;
    }

    public function modelHasher($model, ...$params): string {
        return $this->smTableEncryption->makeHash([
            trim($model['name']),
            $params[0],
            strval($model['credit_balance']),
            trim($model['meters'][0]['current_tariff_name']),
            $model['meters'][0]['serial'],
        ]);
    }

    public function getSparkCustomersWithAddress() {
        return $this->smCustomer->newQuery()->with([
            'mpmPerson.addresses',
        ])->whereHas('mpmPerson.addresses', function ($q) {
            return $q->where('is_primary', 1);
        })->get();
    }

    public function syncCheckBySite($siteId): array {
        try {
            $sparkCustomers = $this->sparkMeterApiRequests->get('/customers', $siteId);
        } catch (SparkAPIResponseException $e) {
            Log::critical('Spark meter customers sync-check-by-site failed.', ['Error :' => $e->getMessage()]);
            throw new SparkAPIResponseException($e->getMessage());
        }

        $sparkCustomersCollection = collect($sparkCustomers['customers'])->filter(function (array $customer): bool {
            return $customer['id'] && $customer['meters'][0]['current_tariff_name'];
        });

        $sparkCustomers = $this->smCustomer->newQuery()->where('site_id', $siteId)->get();
        $people = $this->person->newQuery()->get();

        $sparkCustomersCollection->transform(function (array $customer) use ($sparkCustomers, $people): array {
            $registeredSparkCustomer = $sparkCustomers->firstWhere('customer_id', $customer['id']);
            $relatedPerson = null;
            $phone = $customer['phone_number'] == null ? 'NA' : $customer['phone_number'];
            $customerHash = $this->modelHasher($customer, $phone);
            if ($registeredSparkCustomer) {
                $customer['syncStatus'] = $customerHash === $registeredSparkCustomer->hash ?
                    SyncStatus::SYNCED : SyncStatus::MODIFIED;
                $relatedPerson = $people->find($registeredSparkCustomer->mpm_customer_id);
            } else {
                $customer['syncStatus'] = SyncStatus::NOT_REGISTERED_YET;
            }
            $customer['hash'] = $customerHash;
            $customer['relatedPerson'] = $relatedPerson;
            $customer['registeredSparkCustomer'] = $registeredSparkCustomer;

            return $customer;
        });
        $customerSyncStatus = $sparkCustomersCollection->whereNotIn('syncStatus', [1])->count();
        if ($customerSyncStatus) {
            return ['result' => false, 'message' => 'customers are not updated for site '.$siteId];
        } else {
            return ['result' => true, 'message' => 'Records are updated'];
        }
    }

    public function resetMeter($customer): void {
        $rootUrl = '/customers/'.$customer->customer_id.'/reset-meter';
        try {
            $this->sparkMeterApiRequests->post($rootUrl, null, $customer->site->site_id);
        } catch (SparkAPIResponseException $e) {
            Log::critical('Spark meter customer meter reset failed.', ['Error :' => $e->getMessage()]);
            throw new SparkAPIResponseException($e->getMessage());
        }
    }

    public function getSparkCustomerWithPhone($phoneNumber) {
        $person = $this->person->newQuery()->with(['addresses'])
            ->whereHas(
                'addresses',
                static function ($q) use ($phoneNumber) {
                    $q->where('phone', $phoneNumber);
                }
            )->first();

        return $this->smCustomer->newQuery()->with(['site', 'mpmPerson.devices.device'])->where(
            'mpm_customer_id',
            $person->id
        )->first();
    }
}
