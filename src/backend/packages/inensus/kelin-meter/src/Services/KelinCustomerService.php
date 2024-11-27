<?php

namespace Inensus\KelinMeter\Services;

use App\Models\City;
use App\Models\Person\Person;
use App\Services\AddressesService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inensus\KelinMeter\Exceptions\KelinApiResponseException;
use Inensus\KelinMeter\Helpers\ApiHelpers;
use Inensus\KelinMeter\Http\Clients\KelinMeterApiClient;
use Inensus\KelinMeter\Models\KelinCustomer;
use Inensus\KelinMeter\Models\SyncStatus;

class KelinCustomerService implements ISynchronizeService {
    private $rootUrl = '/listCons';
    private $kelinCustomer;
    private $person;
    private $kelinApiClient;
    private $apiHelpers;
    private $syncSettingService;
    private $syncActionService;

    public function __construct(
        KelinCustomer $kelinCustomer,
        Person $person,
        KelinMeterApiClient $kelinApiClient,
        ApiHelpers $apiHelpers,
        KelinSyncSettingService $syncSettingService,
        KelinSyncActionService $syncActionService,
    ) {
        $this->kelinCustomer = $kelinCustomer;
        $this->person = $person;
        $this->kelinApiClient = $kelinApiClient;
        $this->apiHelpers = $apiHelpers;
        $this->syncActionService = $syncActionService;
        $this->syncSettingService = $syncSettingService;
    }

    public function sync() {
        $synSetting = $this->syncSettingService->getSyncSettingsByActionName('Customers');
        $syncAction = $this->syncActionService->getSyncActionBySynSettingId($synSetting->id);
        try {
            $syncCheck = $this->syncCheck(true);

            $syncCheck['data']->filter(function ($value) {
                return $value['syncStatus'] === SyncStatus::EARLY_REGISTERED;
            })->each(function ($customer) {
                $person = $this->updateRelatedPerson(
                    $customer,
                    $customer['relatedPerson']
                );
                try {
                    $this->kelinCustomer->newQuery()->create([
                        'customer_no' => $customer['consNo'],
                        'mpm_customer_id' => $person->id,
                        'address' => $customer['address'],
                        'mobile' => $customer['mobile'],
                        'hash' => $customer['hash'],
                    ]);
                } catch (\Exception $exception) {
                    $phone = ltrim($customer['mobile'], $customer['mobile'][0]);
                    $addresses = DB::table('addresses')->where('phone', 'LIKE', '%'.$phone.'%')->where('owner_type', 'person')
                        ->get();
                    $this->kelinCustomer->newQuery()->create([
                        'customer_no' => $customer['consNo'],
                        'mpm_customer_id' => $addresses[1]->owner_id,
                        'address' => $customer['address'],
                        'mobile' => $customer['mobile'],
                        'hash' => $customer['hash'],
                    ]);
                }
            });
            $syncCheck['data']->filter(function ($value) {
                return $value['syncStatus'] === SyncStatus::NOT_REGISTERED_YET;
            })->each(function ($customer) {
                $person = $this->createRelatedPerson($customer);

                $this->kelinCustomer->newQuery()->create([
                    'customer_no' => $customer['consNo'],
                    'mpm_customer_id' => $person->id,
                    'address' => $customer['address'],
                    'mobile' => $customer['mobile'],
                    'hash' => $customer['hash'],
                ]);
            });
            $syncCheck['data']->filter(function ($value) {
                return $value['syncStatus'] === SyncStatus::MODIFIED;
            })->each(function ($customer) {
                $person = is_null($customer['relatedPerson']) ?
                    $this->createRelatedPerson($customer) : $this->updateRelatedPerson(
                        $customer,
                        $customer['relatedPerson']
                    );
                $customer['registeredKelinCustomer']->update([
                    'customer_no' => $customer['consNo'],
                    'mpm_customer_id' => $person->id,
                    'address' => $customer['address'],
                    'mobile' => $customer['mobile'],
                    'hash' => $customer['hash'],
                ]);
            });
            $this->syncActionService->updateSyncAction($syncAction, $synSetting, true);

            return $this->kelinCustomer->newQuery()->with([
                'mpmPerson.addresses',
            ])->paginate(config('kelin-meter.paginate'));
        } catch (\Exception $exception) {
            $this->syncActionService->updateSyncAction($syncAction, $synSetting, false);
            Log::critical('Kelin customers sync failed.', ['Error :' => $exception->getMessage()]);
            throw new \Exception($exception->getMessage());
        }
    }

    public function syncCheck($returnData = false) {
        try {
            $url = $this->rootUrl;
            $result = $this->kelinApiClient->get($url);
            $customers = $result['data'];
        } catch (KelinApiResponseException $exception) {
            if ($returnData) {
                return ['result' => false];
            }
            throw new KelinApiResponseException($exception->getMessage());
        }
        $customersCollection = collect($customers)->filter(function ($q) {
            return $q['consNo'] !== '';
        });

        $kelinCustomers = $this->kelinCustomer->newQuery()->get();
        $people = $this->person->newQuery()->get();

        $customersCollection->transform(function ($customer) use ($kelinCustomers, $people) {
            $customerHash = $this->kelinCustomerHasher($customer);
            $earlyRegisteredPerson = $this->findRegisteredCustomer($customer);
            $registeredKelinCustomer = $kelinCustomers->firstWhere('customer_no', $customer['consNo']);
            if ($earlyRegisteredPerson) {
                $customer['hash'] = $customerHash;
                $customer['syncStatus'] = SyncStatus::EARLY_REGISTERED;
                if ($registeredKelinCustomer) {
                    $customer['syncStatus'] = $customerHash === $registeredKelinCustomer->hash ?
                        SyncStatus::SYNCED : SyncStatus::MODIFIED;
                }
                $customer['relatedPerson'] = $earlyRegisteredPerson;
                $customer['registeredKelinCustomer'] = null;

                return $customer;
            } else {
                $relatedPerson = null;
                if ($registeredKelinCustomer) {
                    $customer['syncStatus'] = $customerHash === $registeredKelinCustomer->hash ?
                        SyncStatus::SYNCED : SyncStatus::MODIFIED;
                    $relatedPerson = $people->where('id', $registeredKelinCustomer->mpm_customer_id)->first();
                } else {
                    $customer['syncStatus'] = SyncStatus::NOT_REGISTERED_YET;
                }
                $customer['hash'] = $customerHash;
                $customer['relatedPerson'] = $relatedPerson;
                $customer['registeredKelinCustomer'] = $registeredKelinCustomer;

                return $customer;
            }
        });
        $customerSyncStatus = $customersCollection->whereNotIn('syncStatus', SyncStatus::SYNCED)->count();

        if ($customerSyncStatus) {
            return $returnData ? ['data' => $customersCollection, 'result' => false] : ['result' => false];
        }

        return $returnData ? ['data' => $customersCollection, 'result' => true] : ['result' => true];
    }

    public function createRelatedPerson($customer) {
        $names = explode(' ', $customer['consName']);
        $phone = ltrim($customer['mobile'], $customer['mobile'][0]);

        if (count($names) == 1) {
            $name = $customer['consName'];
            $surname = '';
        } elseif (count($names) == 2) {
            $name = $names[0];
            $surname = $names[1];
        } else {
            $name = implode(' ', array_slice($names, 0, count($names) - 1));
            $surname = $names[count($names) - 1];
        }
        $personData = [
            'name' => $name,
            'surname' => $surname,
            'phone' => '+234'.$phone,
            'street1' => $customer['address'] ?? null,
        ];
        $person = $this->person->newQuery()->create([
            'name' => $personData['name'],
            'surname' => $personData['surname'],
            'is_customer' => 1,
        ]);
        $addressService = App::make(AddressesService::class);
        $city = City::query()->first();
        $addressParams = [
            'phone' => $personData['phone'],
            'street' => $personData['street1'],
            'is_primary' => 1,
            'city_id' => $city ? $city->id : 1,
        ];
        $address = $addressService->instantiate($addressParams);
        $addressService->assignAddressToOwner($person, $address);

        return $person;
    }

    public function updateRelatedPerson($customer, $person) {
        $names = explode(' ', $customer['consName']);
        if (count($names) == 1) {
            $name = $customer['consName'];
            $surname = '';
        } elseif (count($names) == 2) {
            $name = $names[0];
            $surname = $names[1];
        } else {
            $name = implode(' ', array_slice($names, 0, count($names) - 1));
            $surname = $names[count($names) - 1];
        }
        $person->update([
            'name' => $name,
            'surname' => $surname,
        ]);
        $address = $person->addresses()->where('is_primary', 1)->first();
        $phone = ltrim($customer['mobile'], $customer['mobile'][0]);
        $address->update([
            'phone' => '+234'.$phone,
            'street' => $customer['address'] ?: null,
        ]);

        return $person;
    }

    public function getCustomers($request) {
        $perPage = $request->input('per_page') ?? 15;

        return $this->kelinCustomer->newQuery()->with(['mpmPerson.addresses'])->paginate($perPage);
    }

    private function kelinCustomerHasher($kelinCustomer) {
        $phone = ltrim($kelinCustomer['mobile'], $kelinCustomer['mobile'][0]);

        return $this->apiHelpers->makeHash([
            $kelinCustomer['consNo'],
            $kelinCustomer['address'],
            '+234'.$phone,
            $kelinCustomer['consName'],
        ]);
    }

    private function findRegisteredCustomer($customer) {
        return $this->person->newQuery()->where('title', $customer['consNo'])->first();
    }
}
