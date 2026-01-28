<?php

namespace App\Plugins\SparkMeter\Services;

use App\Models\Address\Address;
use App\Models\ConnectionGroup;
use App\Models\ConnectionType;
use App\Models\GeographicalInformation;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use App\Plugins\SparkMeter\Exceptions\SparkAPIResponseException;
use App\Plugins\SparkMeter\Helpers\SmTableEncryption;
use App\Plugins\SparkMeter\Http\Requests\SparkMeterApiRequests;
use App\Plugins\SparkMeter\Models\SmCustomer;
use App\Plugins\SparkMeter\Models\SmMeterModel;
use App\Plugins\SparkMeter\Models\SmSite;
use App\Plugins\SparkMeter\Models\SmTariff;
use App\Plugins\SparkMeter\Models\SyncStatus;
use App\Services\AddressesService;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * @implements ISynchronizeService<SmCustomer>
 */
class CustomerService implements ISynchronizeService {
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

    /**
     * @return LengthAwarePaginator<int, SmCustomer>
     */
    public function getSmCustomers(Request $request): LengthAwarePaginator {
        $perPage = (int) $request->input('per_page', 15);

        return $this->smCustomer->newQuery()->with(['mpmPerson', 'site.mpmMiniGrid'])->paginate($perPage);
    }

    public function getSmCustomersCount(): int {
        return count($this->smCustomer->newQuery()->get());
    }

    public function getSmCustomerByCustomerId(int $customerId): ?SmCustomer {
        return $this->smCustomer->newQuery()->with([
            'mpmPerson.devices.device',
            'mpmPerson.addresses',
        ])->where('customer_id', $customerId)->first();
    }

    /**
     * @param array<string, mixed> $customerData
     */
    public function updateSparkCustomerInfo(array $customerData, string $siteId): string {
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

    /**
     * @param array<string, mixed> $customer
     */
    public function createRelatedPerson(array $customer, string $site_id): int {
        try {
            DB::connection('tenant')->beginTransaction();
            $sparkCustomerMeterSerial = $customer['meters'][0]['serial'];
            $meter = $this->meter->newQuery()->where('serial_number', $sparkCustomerMeterSerial)->first();
            $person = null;
            if ($meter === null) {
                $meter = new Meter();
                $geoLocation = new GeographicalInformation();
            } else {
                $geoLocation = $meter->device->geo()->first();
                if ($geoLocation === null) {
                    $geoLocation = new GeographicalInformation();
                }
                $person = $this->person->newQuery()->whereHas('devices.device', static fn ($q) => $q->where('id', $meter->id))->first();
            }
            if ($person === null) {
                $data = [
                    'name' => $customer['name'] ?: '',
                    'phone' => $customer['phone_number'] ?: null,
                    'street1' => $customer['meters'][0]['street1'] ?: null,
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
            $meter->updated_at = now();
            $meter->save();

            $geoLocation->points = $customer['meters'][0]['coords'];
            $connectionType = $this->connectionType->newQuery()->first();
            $connectionGroup = $this->connectionGroup->newQuery()->first();

            $meter->device->person()->associate($person);
            $currentTariffName = $customer['meters'][0]['current_tariff_name'];

            $smTariff = $this->smTariff->newQuery()->with('mpmTariff')->whereHas(
                'mpmTariff',
                fn ($q) => $q->where('name', $currentTariffName)
            )->first();
            if ($smTariff) {
                $meter->tariff()->associate($smTariff->mpmTariff);
            }
            $meter->save();
            if ($geoLocation->points == null) {
                $geoLocation->points = config('spark.geoLocation');
            }
            $meter->device->geo()->save($geoLocation);

            $site = $this->smSite->newQuery()->with('mpmMiniGrid')->where('site_id', $site_id)->firstOrFail();

            $sparkCity = $site->mpmMiniGrid->cities[0];

            $address = new Address();
            $address = $address->newQuery()->create([
                'city_id' => request()->input('city_id', $sparkCity->id),
            ]);
            $address->owner()->associate($meter->device);
            $address->save();
            DB::connection('tenant')->commit();

            return $person->id;
        } catch (\Exception $e) {
            DB::connection('tenant')->rollBack();
            Log::critical('Error while synchronizing spark customers', ['message' => $e->getMessage()]);
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createPerson(array $data): Person {
        $person = $this->person->newQuery()->create([
            'name' => $data['name'],
            'is_customer' => 1,
        ]);
        $addressService = app()->make(AddressesService::class);

        $addressParams = [
            'phone' => $data['phone'],
            'street' => $data['street1'],
            'is_primary' => 1,
        ];
        $address = $addressService->instantiate($addressParams);
        $addressService->assignAddressToOwner($person, $address);

        return $person;
    }

    /**
     * @param array<string,mixed> $customer
     */
    public function updateRelatedPerson(array $customer, Person $person, string $site_id): void {
        $sparkCustomerMeterSerial = $customer['meters'][0]['serial'];
        $currentTariffName = $customer['meters'][0]['current_tariff_name'];
        $site = $this->smSite->newQuery()->with('mpmMiniGrid')->where('site_id', $site_id)->firstOrFail();
        $sparkCity = $site->mpmMiniGrid->cities[0];
        $address = $person->addresses()->where('is_primary', 1)->first();
        $address->update([
            'phone' => $customer['phone_number'],
            'street' => $customer['meters'][0]['street1'],
        ]);
        $meter = $person->devices()->first()->device;
        $meter->device->address()->update([
            'city_id' => $sparkCity->id,
        ]);
        if ($meter instanceof Meter) {
            $meter->update([
                'serial_number' => $sparkCustomerMeterSerial,
            ]);
        }
        $smTariff = $this->smTariff->newQuery()->with(['mpmTariff'])->whereHas(
            'mpmTariff',
            fn ($q) => $q->where('name', $currentTariffName)
        )->first();

        if ($smTariff) {
            $meter->tariff()->associate($smTariff->mpmTariff);
            $meter->save();
        }
        $geo = $meter->device->geo()->first();
        if ($geo && array_key_exists('coords', $customer['meters'][0])) {
            $geo->points = $customer['meters'][0]['coords'] === '' ?
                config('spark.geoLocation') : $customer['meters'][0]['coords'];
            $geo->update();
        }
        $person->update([
            'name' => $customer['name'],
            'surname' => '',
            'updated_at' => date('Y-m-d h:i:s'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
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

    public function hashCustomerWithMeterSerial(string $meterSerial, string $siteId): string {
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

    /**
     * @param array<string, mixed> $data
     */
    public function updateCustomerLowBalanceLimit(int $customerId, array $data): ?SmCustomer {
        $customer = $this->smCustomer->newQuery()->find($customerId);
        $customer->update([
            'low_balance_limit' => $data['low_balance_limit'],
        ]);

        return $customer->fresh();
    }

    /**
     * @return LengthAwarePaginator<int, SmCustomer>|Collection<int, SmCustomer>
     */
    public function searchCustomer(string $searchTerm, bool $paginate): LengthAwarePaginator|Collection {
        if ($paginate) {
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

    /**
     * @return Collection<int, SmCustomer>
     */
    public function getLowBalancedCustomers(): Collection {
        return $this->smCustomer->newQuery()->with([
            'mpmPerson.addresses' => fn ($q) => $q->where('is_primary', 1),
        ])
            ->whereNotNull('low_balance_limit')
            ->where('low_balance_limit', '>', 0)
            ->where('low_balance_limit', '>', 'credit_balance')->get();
    }

    /**
     * @return LengthAwarePaginator<int, SmCustomer>
     */
    public function sync(): LengthAwarePaginator {
        $synSetting = $this->smSyncSettingService->getSyncSettingsByActionName('Customers');
        $syncAction = $this->smSyncActionService->getSyncActionBySynSettingId($synSetting->id);
        try {
            $syncCheck = $this->syncCheck(true);
            $customersCollection = collect($syncCheck)->except('available_site_count');
            $customersCollection->each(function (array $customers) {
                $customers['site_data']->filter(fn (array $customer): bool => $customer['syncStatus'] === SyncStatus::NOT_REGISTERED_YET)->each(function (array $customer) use ($customers) {
                    $mpmCustomerId = $this->createRelatedPerson($customer, $customers['site_id']);
                    $this->smCustomer->newQuery()->create([
                        'customer_id' => $customer['id'],
                        'mpm_customer_id' => $mpmCustomerId,
                        'site_id' => $customers['site_id'],
                        'credit_balance' => $customer['credit_balance'],
                        'hash' => $customer['hash'],
                    ]);
                });
                $customers['site_data']->filter(fn (array $customer): bool => $customer['syncStatus'] === SyncStatus::MODIFIED)->each(function (array $customer) use ($customers) {
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
     * @return array<string, mixed>
     */
    public function syncCheck(bool $returnData = false): array {
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

            // @phpstan-ignore argument.templateType,argument.templateType
            $sparkCustomersCollection = collect($sparkCustomers['customers'])->filter(fn (array $customer): bool => $customer['id'] && $customer['meters'][0]['current_tariff_name']);
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

    /**
     * @param array<string, mixed> $model
     */
    public function modelHasher(array $model, ?string ...$params): string {
        return $this->smTableEncryption->makeHash([
            trim($model['name']),
            $params[0],
            strval($model['credit_balance']),
            trim($model['meters'][0]['current_tariff_name']),
            $model['meters'][0]['serial'],
        ]);
    }

    /**
     * @return Collection<int, SmCustomer>
     */
    public function getSparkCustomersWithAddress(): Collection {
        return $this->smCustomer->newQuery()->with([
            'mpmPerson.addresses',
        ])->whereHas('mpmPerson.addresses', fn ($q) => $q->where('is_primary', 1))->get();
    }

    /**
     * @return array{result: bool, message: string}
     */
    public function syncCheckBySite(string $siteId): array {
        try {
            $sparkCustomers = $this->sparkMeterApiRequests->get('/customers', $siteId);
        } catch (SparkAPIResponseException $e) {
            Log::critical('Spark meter customers sync-check-by-site failed.', ['Error :' => $e->getMessage()]);
            throw new SparkAPIResponseException($e->getMessage());
        }

        // @phpstan-ignore argument.templateType,argument.templateType
        $sparkCustomersCollection = collect($sparkCustomers['customers'])->filter(fn (array $customer): bool => $customer['id'] && $customer['meters'][0]['current_tariff_name']);

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

    public function resetMeter(SmCustomer $customer): void {
        $rootUrl = '/customers/'.$customer->customer_id.'/reset-meter';
        try {
            $this->sparkMeterApiRequests->post($rootUrl, null, $customer->site->site_id);
        } catch (SparkAPIResponseException $e) {
            Log::critical('Spark meter customer meter reset failed.', ['Error :' => $e->getMessage()]);
            throw new SparkAPIResponseException($e->getMessage());
        }
    }

    public function getSparkCustomerWithPhone(string $phoneNumber): ?SmCustomer {
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
