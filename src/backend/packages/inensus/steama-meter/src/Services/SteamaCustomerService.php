<?php

namespace Inensus\SteamaMeter\Services;

use App\Models\City;
use App\Models\ConnectionType;
use App\Models\Person\Person;
use App\Services\AddressesService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Inensus\SteamaMeter\Exceptions\ModelNotFoundException;
use Inensus\SteamaMeter\Exceptions\SteamaApiResponseException;
use Inensus\SteamaMeter\Helpers\ApiHelpers;
use Inensus\SteamaMeter\Http\Clients\SteamaMeterApiClient;
use Inensus\SteamaMeter\Models\SteamaCustomer;
use Inensus\SteamaMeter\Models\SteamaCustomerBasisPaymentPlan;
use Inensus\SteamaMeter\Models\SteamaFlatRatePaymentPlan;
use Inensus\SteamaMeter\Models\SteamaHybridPaymentPlan;
use Inensus\SteamaMeter\Models\SteamaMinimumTopUpRequirementsPaymentPlan;
use Inensus\SteamaMeter\Models\SteamaPerKwhPaymentPlan;
use Inensus\SteamaMeter\Models\SteamaSite;
use Inensus\SteamaMeter\Models\SteamaSubscriptionPaymentPlan;
use Inensus\SteamaMeter\Models\SteamaUserType;
use Inensus\SteamaMeter\Models\SyncStatus;

class SteamaCustomerService implements ISynchronizeService {
    private $customer;
    private $steamaApi;
    private $apiHelpers;
    private $rootUrl = '/customers';
    private $person;
    private $customerBasisPaymentPlan;
    private $flatRatePaymentPlan;
    private $subscriptionPaymentPlan;
    private $hybridPaymentPlan;
    private $minimumTopUpPaymentPlan;
    private $perKwhPaymentPlan;
    private $userType;
    private $connectionType;
    private $steamaSite;
    private $bitharvesterService;
    private $stmSite;
    private $city;
    private $steamaSyncSettingService;
    private $steamaSyncActionService;
    private $steamaSmsNotifiedCustomerService;

    public function __construct(
        SteamaCustomer $steamaCustomerModel,
        SteamaMeterApiClient $steamaApi,
        ApiHelpers $apiHelpers,
        Person $person,
        SteamaFlatRatePaymentPlan $flatRatePaymentPlan,
        SteamaCustomerBasisPaymentPlan $customerBasisPaymentPlan,
        SteamaSubscriptionPaymentPlan $subscriptionPaymentPlan,
        SteamaHybridPaymentPlan $hybridPaymentPlan,
        SteamaMinimumTopUpRequirementsPaymentPlan $minimumTopUpPaymentPlan,
        SteamaPerKwhPaymentPlan $perKwhPaymentPlan,
        SteamaUserType $userType,
        ConnectionType $connectionType,
        SteamaSite $steamaSite,
        SteamaBitharvesterService $bitharvesterService,
        SteamaSite $stmSite,
        City $city,
        SteamaSyncSettingService $steamaSyncSettingService,
        StemaSyncActionService $steamaSyncActionService,
        SteamaSmsNotifiedCustomerService $steamaSmsNotifiedCustomerService,
    ) {
        $this->customer = $steamaCustomerModel;
        $this->apiHelpers = $apiHelpers;
        $this->steamaApi = $steamaApi;
        $this->person = $person;
        $this->flatRatePaymentPlan = $flatRatePaymentPlan;
        $this->customerBasisPaymentPlan = $customerBasisPaymentPlan;
        $this->subscriptionPaymentPlan = $subscriptionPaymentPlan;
        $this->hybridPaymentPlan = $hybridPaymentPlan;
        $this->minimumTopUpPaymentPlan = $minimumTopUpPaymentPlan;
        $this->perKwhPaymentPlan = $perKwhPaymentPlan;
        $this->userType = $userType;
        $this->connectionType = $connectionType;
        $this->steamaSite = $steamaSite;
        $this->bitharvesterService = $bitharvesterService;
        $this->stmSite = $stmSite;
        $this->city = $city;
        $this->steamaSyncSettingService = $steamaSyncSettingService;
        $this->steamaSyncActionService = $steamaSyncActionService;
        $this->steamaSmsNotifiedCustomerService = $steamaSmsNotifiedCustomerService;
    }

    public function getCustomers($request) {
        $perPage = $request->input('per_page') ?? 15;

        return $this->customer->newQuery()->with(['mpmPerson.addresses', 'site.mpmMiniGrid'])->paginate($perPage);
    }

    public function getCustomersCount() {
        return count($this->customer->newQuery()->get());
    }

    public function sync() {
        $synSetting = $this->steamaSyncSettingService->getSyncSettingsByActionName('Customers');
        $syncAction = $this->steamaSyncActionService->getSyncActionBySynSettingId($synSetting->id);
        try {
            $syncCheck = $this->syncCheck(true);
            $userTypes = $this->userType->newQuery()->get();
            $syncCheck['data']->filter(function ($value) {
                return $value['syncStatus'] === SyncStatus::NOT_REGISTERED_YET;
            })->each(function ($customer) use ($userTypes) {
                $person = $this->createRelatedPerson($customer);
                $userType = $userTypes->where('syntax', $customer['user_type'])->first();
                $this->customer->newQuery()->create([
                    'customer_id' => $customer['id'],
                    'mpm_customer_id' => $person->id,
                    'user_type_id' => $userType->id,
                    'energy_price' => floatval($customer['energy_price']),
                    'low_balance_warning' => floatval($customer['low_balance_warning']),
                    'account_balance' => floatval($customer['account_balance']),
                    'site_id' => $customer['site'],
                    'hash' => $customer['hash'],
                ]);
                $this->setStmCustomerPaymentPlan($customer);
            });

            $syncCheck['data']->filter(function ($value) {
                return $value['syncStatus'] === SyncStatus::MODIFIED;
            })->each(function ($customer) use ($userTypes) {
                $person = is_null($customer['relatedPerson']) ?
                    $this->createRelatedPerson($customer) : $this->updateRelatedPerson(
                        $customer,
                        $customer['relatedPerson']
                    );
                $userType = $userTypes->where('syntax', $customer['user_type'])->first();
                $customer['registeredStmCustomer']->update([
                    'customer_id' => $customer['id'],
                    'mpm_customer_id' => $person->id,
                    'user_type_id' => $userType->id,
                    'energy_price' => floatval($customer['energy_price']),
                    'low_balance_warning' => floatval($customer['low_balance_warning']),
                    'account_balance' => floatval($customer['account_balance']),
                    'site_id' => $customer['site'],
                    'hash' => $customer['hash'],
                ]);
                $this->setStmCustomerPaymentPlan($customer);
                $this->steamaSmsNotifiedCustomerService->removeLowBalancedCustomer($customer['registeredStmCustomer']);
            });
            $this->steamaSyncActionService->updateSyncAction($syncAction, $synSetting, true);

            return $this->customer->newQuery()->with([
                'mpmPerson.addresses',
                'site.mpmMiniGrid',
            ])->paginate(config('steama.paginate'));
        } catch (\Exception $e) {
            $this->steamaSyncActionService->updateSyncAction($syncAction, $synSetting, false);
            Log::critical('Steama customers sync failed.', ['Error :' => $e->getMessage()]);
            throw new \Exception($e->getMessage());
        }
    }

    public function syncCheck($returnData = false) {
        try {
            $url = $this->rootUrl.'?page=1&page_size=100';
            $result = $this->steamaApi->get($url);

            $customers = $result['results'];
            while ($result['next']) {
                $url = $this->rootUrl.'?'.explode('?', $result['next'])[1];
                $result = $this->steamaApi->get($url);
                foreach ($result['results'] as $customer) {
                    array_push($customers, $customer);
                }
            }
        } catch (SteamaApiResponseException $e) {
            if ($returnData) {
                return ['result' => false];
            }
            throw new SteamaApiResponseException($e->getMessage());
        }
        $customersCollection = collect($customers);
        $stmCustomers = $this->customer->newQuery()->get();
        $people = $this->person->newQuery()->get();
        $customersCollection->transform(function ($customer) use ($stmCustomers, $people) {
            $registeredStmCustomer = $stmCustomers->firstWhere('customer_id', $customer['id']);
            $relatedPerson = null;
            $customerHash = $this->steamaCustomerHasher($customer);
            if ($registeredStmCustomer) {
                $customer['syncStatus'] = $customerHash === $registeredStmCustomer->hash ?
                    SyncStatus::SYNCED : SyncStatus::MODIFIED;
                $relatedPerson = $people->where('id', $registeredStmCustomer->mpm_customer_id)->first();
            } else {
                $customer['syncStatus'] = SyncStatus::NOT_REGISTERED_YET;
            }
            $customer['hash'] = $customerHash;
            $customer['relatedPerson'] = $relatedPerson;
            $customer['registeredStmCustomer'] = $registeredStmCustomer;

            return $customer;
        });
        $customerSyncStatus = $customersCollection->whereNotIn('syncStatus', SyncStatus::SYNCED)->count();
        if ($customerSyncStatus) {
            return $returnData ? ['data' => $customersCollection, 'result' => false] : ['result' => false];
        }

        return $returnData ? ['data' => $customersCollection, 'result' => true] : ['result' => true];
    }

    public function createRelatedPerson($customer) {
        $personData = [
            'name' => $customer['first_name'] ? $customer['first_name'] : '',
            'surname' => $customer['last_name'] ? $customer['last_name'] : '',
            'phone' => $customer['telephone'] ? $customer['telephone'] : null,
            'street1' => $customer['site_name'] ? $customer['site_name'] : null,
        ];
        $customerSite = $this->stmSite->newQuery()->with('mpmMiniGrid')->where('site_id', $customer['site'])->first();
        $customerCity = $this->city->newQuery()->where('mini_grid_id', $customerSite->mpmMiniGrid->id)->first();

        $person = $this->person->newQuery()->create([
            'name' => $personData['name'],
            'surname' => $personData['surname'],
            'is_customer' => 1,
        ]);
        $addressService = App::make(AddressesService::class);
        $addressParams = [
            'phone' => $personData['phone'],
            'street' => $personData['street1'],
            'is_primary' => 1,
            'city_id' => $customerCity->id,
        ];
        $address = $addressService->instantiate($addressParams);
        $addressService->assignAddressToOwner($person, $address);

        return $person;
    }

    public function updateRelatedPerson($customer, $person) {
        $person->update([
            'name' => $customer['first_name'] ? $customer['first_name'] : '',
            'surname' => $customer['last_name'] ? $customer['last_name'] : '',
        ]);
        $customerSite = $this->stmSite->newQuery()->with('mpmMiniGrid')->where('site_id', $customer['site'])->first();
        $customerCity = $this->city->newQuery()->where('mini_grid_id', $customerSite->mpmMiniGrid->id)->first();

        $address = $person->addresses()->where('is_primary', 1)->first();
        $address->update([
            'phone' => $customer['telephone'] ? $customer['telephone'] : null,
            'street' => $customer['site_name'] ? $customer['site_name'] : null,
            'city_id' => $customerCity->id,
        ]);

        return $person;
    }

    public function createSteamaCustomer($meterInfo) {
        $miniGrid = $meterInfo->address->city->miniGrid;
        $steamaSite = $this->steamaSite->newQuery()->where('mpm_mini_grid_id', $miniGrid->id)->first();
        $person = $meterInfo->owner;
        $personAddress = $person->addresses->where('is_primary', 1)->first();
        $userType = $this->userType->newQuery()->where(
            'mpm_connection_type_id',
            $meterInfo->connection_type_id
        )->first();
        $bitHarvesterId = $this->bitharvesterService->getBitharvester($steamaSite->id)['id'];
        $postParams = [
            'first_name' => $person->name,
            'last_name' => $person->surname,
            'telephone' => $personAddress->phone,
            'site' => $steamaSite->site_id,
            'bit_harvester' => $bitHarvesterId,
            'user_type' => $userType->syntax,
            'payment_plan' => '',
            'status' => 'on',
            'control_type' => 'AUTOC',
        ];
        $customer = $this->steamaApi->post($this->rootUrl.'/', $postParams);
        $customerHash = $this->steamaCustomerHasher($customer);

        return $this->customer->newQuery()->create([
            'customer_id' => $customer['id'],
            'mpm_customer_id' => $person->id,
            'energy_price' => $customer['energy_price'],
            'low_balance_warning' => $customer['low_balance_warning'],
            'site_id' => $customer['site'],
            'hash' => $customerHash,
        ]);
    }

    public function syncTransactionCustomer($stmCustomerId) {
        $url = $this->rootUrl.'/'.strval($stmCustomerId);
        $customer = $this->steamaApi->get($url);
        try {
            $stmCustomer = $this->customer->newQuery()->where('customer_id', $customer['id'])->firstOrFail();
            $relatedPerson = $this->person->newQuery()->where('id', $stmCustomer->mpm_customer_id)->firstOrFail();
            $userType = $this->userType->newQuery()->where('syntax', $customer['user_type'])->firstOrFail();
            $stmCustomerHash = $this->steamaCustomerHasher($customer);
            $stmCustomer->update([
                'customer_id' => $customer['id'],
                'mpm_customer_id' => $relatedPerson->id,
                'user_type_id' => $userType->id,
                'energy_price' => floatval($customer['energy_price']),
                'low_balance_warning' => floatval($customer['low_balance_warning']),
                'site_id' => $customer['site'],
                'hash' => $stmCustomerHash,
            ]);
            $this->setStmCustomerPaymentPlan($customer);

            return $stmCustomer->fresh();
        } catch (ModelNotFoundException $e) {
            throw new ModelNotFoundException($e->getMessage());
        }
    }

    public function updateSteamaCustomerInfo($stmCustomer, $putData) {
        try {
            $url = $this->rootUrl.'/'.strval($stmCustomer->customer_id);
            $updatedSteamaCustomer = $this->steamaApi->patch($url, $putData);
            $smCustomerHash = $this->steamaCustomerHasher($updatedSteamaCustomer);
            $stmCustomer->update([
                'hash' => $smCustomerHash,
            ]);

            return $stmCustomer->fresh();
        } catch (ModelNotFoundException $e) {
            throw new SteamaApiResponseException($e->getMessage());
        }
    }

    public function searchCustomer($searchTerm, $paginate) {
        if ($paginate === 1) {
            return $this->customer->newQuery()->with(['mpmPerson.addresses', 'site.mpmMiniGrid'])
                ->WhereHas('site.mpmMiniGrid', function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', '%'.$searchTerm.'%');
                })->orWhereHas('mpmPerson', function ($q) use ($searchTerm) {
                    $q->where('name', 'LIKE', '%'.$searchTerm.'%')->orWhere(
                        'surname',
                        'LIKE',
                        '%'.$searchTerm.'%'
                    );
                })->paginate(15);
        }

        return $this->customer->newQuery()->with(['mpmPerson.addresses', 'site.mpmMiniGrid'])
            ->WhereHas('site.mpmMiniGrid', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%'.$searchTerm.'%');
            })->orWhereHas('mpmPerson', function ($q) use ($searchTerm) {
                $q->where('name', 'LIKE', '%'.$searchTerm.'%')->orWhere('surname', 'LIKE', '%'.$searchTerm.'%');
            })->get();
    }

    public function setStmCustomerPaymentPlan($customer) {
        $plan = explode(',', $customer['payment_plan'])[0];

        switch ($plan) {
            case 'Subscription Plan':
                $customerBasisPlan = $this->customerBasisPaymentPlan->newQuery()
                    ->whereHasMorph('paymentPlan', $this->subscriptionPaymentPlan->getMorphClass())->where(
                        'customer_id',
                        $customer['id']
                    )->first();
                if ($customerBasisPlan) {
                    $customerBasisPlan->paymentPlanSubscription()->delete();
                    $customerBasisPlan->delete();
                }
                $this->setSubscriptionPlan($customer);
                break;

            case 'Hybrid':
                $customerBasisPlan = $this->customerBasisPaymentPlan->newQuery()
                    ->whereHasMorph('paymentPlan', $this->hybridPaymentPlan->getMorphClass())->where(
                        'customer_id',
                        $customer['id']
                    )->first();
                if ($customerBasisPlan) {
                    $customerBasisPlan->paymentPlanHybrid()->delete();
                    $customerBasisPlan->delete();
                }

                $this->setHybridPlan($customer);
                break;
            case 'Minimum Top-Up':
                $customerBasisPlan = $this->customerBasisPaymentPlan->newQuery()
                    ->whereHasMorph('paymentPlan', $this->minimumTopUpPaymentPlan->getMorphClass())->where(
                        'customer_id',
                        $customer['id']
                    )->first();
                if ($customerBasisPlan) {
                    $customerBasisPlan->paymentPlanMinimumTopUp()->delete();
                    $customerBasisPlan->delete();
                }

                $this->setMinimumTopUpPlan($customer);
                break;
            case 'Per kWh':
                $customerBasisPlan = $this->customerBasisPaymentPlan->newQuery()
                    ->whereHasMorph('paymentPlan', $this->perKwhPaymentPlan->getMorphClass())->where(
                        'customer_id',
                        $customer['id']
                    )->first();
                if ($customerBasisPlan) {
                    $customerBasisPlan->paymentPlanPerKwh()->delete();
                    $customerBasisPlan->delete();
                }
                $this->setPerKwhPlan($customer);
                break;
            default:
                $customerBasisPlan = $this->customerBasisPaymentPlan->newQuery()
                    ->whereHasMorph('paymentPlan', $this->flatRatePaymentPlan->getMorphClass())->where(
                        'customer_id',
                        $customer['id']
                    )->first();
                if ($customerBasisPlan) {
                    $customerBasisPlan->paymentPlanFlatRate()->delete();
                    $customerBasisPlan->delete();
                }
                $this->setFlatRatePlan($customer);
                break;
        }
    }

    public function setFlatRatePlan($customer) {
        $customerBasisPlan = $this->customerBasisPaymentPlan->newQuery()->make([
            'customer_id' => $customer['id'],
        ]);
        $flatRatePlan = $this->flatRatePaymentPlan->newQuery()->create([
            'energy_price' => floatval($customer['energy_price']),
        ]);

        $customerBasisPlan->paymentPlan()->associate($flatRatePlan);
        $customerBasisPlan->save();
    }

    public function setPerKwhPlan($customer) {
        $customerBasisPlan = $this->customerBasisPaymentPlan->newQuery()->make([
            'customer_id' => $customer['id'],
        ]);
        $perKwh = $this->perKwhPaymentPlan->newQuery()->create([
            'energy_price' => floatval($customer['energy_price']),
        ]);
        $customerBasisPlan->paymentPlan()->associate($perKwh);
        $customerBasisPlan->save();
    }

    public function setMinimumTopUpPlan($customer) {
        $plan = explode(',', $customer['payment_plan']);
        $threshold = $plan[1];

        $customerBasisPlan = $this->customerBasisPaymentPlan->newQuery()->make([
            'customer_id' => $customer['id'],
        ]);
        $minimumTopUp = $this->minimumTopUpPaymentPlan->newQuery()->create([
            'threshold' => floatval($threshold),
        ]);
        $customerBasisPlan->paymentPlan()->associate($minimumTopUp);
        $customerBasisPlan->save();
    }

    public function setSubscriptionPlan($customer) {
        $plan = explode(',', $customer['payment_plan']);
        $fee = $plan[1];
        $duration = $plan[2];
        $limit = $plan[3];
        $topUpEnabled = $plan[4];

        $customerBasisPlan = $this->customerBasisPaymentPlan->newQuery()->make([
            'customer_id' => $customer['id'],
        ]);

        $subscriptionPlan = $this->subscriptionPaymentPlan->newQuery()->create([
            'plan_fee' => floatval($fee),
            'plan_duration' => $duration,
            'energy_allotment' => floatval($limit),
            'top_ups_enabled' => $topUpEnabled === 1,
        ]);
        $customerBasisPlan->paymentPlan()->associate($subscriptionPlan);
        $customerBasisPlan->save();
    }

    public function setHybridPlan($customer) {
        $plan = explode(',', $customer['payment_plan']);
        $connectionFee = $plan[1];
        $subscriptionCost = $plan[2];
        $daysOfMonth = $plan[3];

        $customerBasisPlan = $this->customerBasisPaymentPlan->newQuery()->make([
            'customer_id' => $customer['id'],
        ]);

        $hybridPlan = $this->hybridPaymentPlan->newQuery()->create([
            'connection_fee' => floatval($connectionFee),
            'subscription_cost' => floatval($subscriptionCost),
            'payment_days_of_month' => $daysOfMonth,
        ]);
        $customerBasisPlan->paymentPlan()->associate($hybridPlan);
        $customerBasisPlan->save();
    }

    public function getSteamaCustomerName($customerId) {
        $stmCustomer = $this->customer->newQuery()->with('mpmPerson')->where('customer_id', $customerId)->first();

        return ['name' => $stmCustomer->mpmPerson->name.' '.$stmCustomer->mpmPerson->surname];
    }

    public function getSteamaCustomersWithAddress() {
        return $this->customer->newQuery()->with([
            'mpmPerson.addresses',
        ])->whereHas('mpmPerson.addresses', function ($q) {
            return $q->where('is_primary', 1);
        });
    }

    private function steamaCustomerHasher($steamaCustomer) {
        return $this->apiHelpers->makeHash([
            $steamaCustomer['user_type'],
            $steamaCustomer['control_type'],
            $steamaCustomer['first_name'],
            $steamaCustomer['last_name'],
            $steamaCustomer['telephone'],
            $steamaCustomer['site'],
            $steamaCustomer['energy_price'],
            $steamaCustomer['is_field_manager'],
            $steamaCustomer['payment_plan'],
            $steamaCustomer['TOU_hours'],
            $steamaCustomer['low_balance_warning'],
            $steamaCustomer['account_balance'],
        ]);
    }

    public function getSteamaCustomerWithPhone($phoneNumber) {
        $person = $this->person::with(['addresses'])
            ->whereHas(
                'addresses',
                static function ($q) use ($phoneNumber) {
                    $q->where('phone', $phoneNumber);
                }
            )->first();

        return $this->customer->newQuery()->with(['site', 'mpmPerson.meters.meter'])->where('mpm_customer_id', $person->id)->first();
    }
}
