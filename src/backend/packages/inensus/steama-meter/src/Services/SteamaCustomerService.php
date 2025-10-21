<?php

namespace Inensus\SteamaMeter\Services;

use App\Models\City;
use App\Models\Person\Person;
use App\Services\AddressesService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
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

/**
 * @implements ISynchronizeService<SteamaCustomer>
 */
class SteamaCustomerService implements ISynchronizeService {
    private string $rootUrl = '/customers';

    public function __construct(
        private SteamaCustomer $customer,
        private SteamaMeterApiClient $steamaApi,
        private ApiHelpers $apiHelpers,
        private Person $person,
        private SteamaFlatRatePaymentPlan $flatRatePaymentPlan,
        private SteamaCustomerBasisPaymentPlan $customerBasisPaymentPlan,
        private SteamaSubscriptionPaymentPlan $subscriptionPaymentPlan,
        private SteamaHybridPaymentPlan $hybridPaymentPlan,
        private SteamaMinimumTopUpRequirementsPaymentPlan $minimumTopUpPaymentPlan,
        private SteamaPerKwhPaymentPlan $perKwhPaymentPlan,
        private SteamaUserType $userType,
        private SteamaSite $stmSite,
        private City $city,
        private SteamaSyncSettingService $steamaSyncSettingService,
        private StemaSyncActionService $steamaSyncActionService,
        private SteamaSmsNotifiedCustomerService $steamaSmsNotifiedCustomerService,
    ) {}

    /**
     * @return LengthAwarePaginator<int, SteamaCustomer>
     */
    public function getCustomers(Request $request): LengthAwarePaginator {
        $perPage = (int) $request->input('per_page', 15);

        return $this->customer->newQuery()->with(['mpmPerson.addresses', 'site.mpmMiniGrid'])->paginate($perPage);
    }

    public function getCustomersCount(): int {
        return count($this->customer->newQuery()->get());
    }

    /**
     * @return LengthAwarePaginator<int, SteamaCustomer>
     */
    public function sync(): LengthAwarePaginator {
        $synSetting = $this->steamaSyncSettingService->getSyncSettingsByActionName('Customers');
        $syncAction = $this->steamaSyncActionService->getSyncActionBySynSettingId($synSetting->id);
        try {
            $syncCheck = $this->syncCheck(true);
            $userTypes = $this->userType->newQuery()->get();
            $syncCheck['data']->filter(fn (array $value): bool => $value['syncStatus'] === SyncStatus::NOT_REGISTERED_YET)->each(function (array $customer) use ($userTypes) {
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

            $syncCheck['data']->filter(fn (array $value): bool => $value['syncStatus'] === SyncStatus::MODIFIED)->each(function (array $customer) use ($userTypes) {
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
            throw new \Exception($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function syncCheck(bool $returnData = false): array {
        try {
            $url = $this->rootUrl.'?page=1&page_size=100';
            $result = $this->steamaApi->get($url);

            $customers = $result['results'];
            while ($result['next']) {
                $url = $this->rootUrl.'?'.explode('?', $result['next'])[1];
                $result = $this->steamaApi->get($url);
                foreach ($result['results'] as $customer) {
                    $customers[] = $customer;
                }
            }
        } catch (SteamaApiResponseException $e) {
            if ($returnData) {
                return ['result' => false];
            }
            throw new SteamaApiResponseException($e->getMessage());
        }
        // @phpstan-ignore argument.templateType,argument.templateType
        $customersCollection = collect($customers);
        $stmCustomers = $this->customer->newQuery()->get();
        $people = $this->person->newQuery()->get();
        $customersCollection->transform(function (array $customer) use ($stmCustomers, $people): array {
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
        $customerSyncStatus = $customersCollection->whereNotIn('syncStatus', [SyncStatus::SYNCED])->count();
        if ($customerSyncStatus) {
            return $returnData ? ['data' => $customersCollection, 'result' => false] : ['result' => false];
        }

        return $returnData ? ['data' => $customersCollection, 'result' => true] : ['result' => true];
    }

    /**
     * @param array<string, mixed> $customer
     */
    public function createRelatedPerson(array $customer): Person {
        $personData = [
            'name' => $customer['first_name'] ?: '',
            'surname' => $customer['last_name'] ?: '',
            'phone' => $customer['telephone'] ?: null,
            'street1' => $customer['site_name'] ?: null,
        ];
        $customerSite = $this->stmSite->newQuery()->with('mpmMiniGrid')->where('site_id', $customer['site'])->first();
        $customerCity = $this->city->newQuery()->where('mini_grid_id', $customerSite->mpmMiniGrid->id)->first();

        $person = $this->person->newQuery()->create([
            'name' => $personData['name'],
            'surname' => $personData['surname'],
            'is_customer' => 1,
        ]);
        $addressService = app()->make(AddressesService::class);
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

    /**
     * @param array<string, mixed> $customer
     */
    public function updateRelatedPerson(array $customer, Person $person): Person {
        $person->update([
            'name' => $customer['first_name'] ?: '',
            'surname' => $customer['last_name'] ?: '',
        ]);
        $customerSite = $this->stmSite->newQuery()->with('mpmMiniGrid')->where('site_id', $customer['site'])->first();
        $customerCity = $this->city->newQuery()->where('mini_grid_id', $customerSite->mpmMiniGrid->id)->first();

        $address = $person->addresses()->where('is_primary', 1)->first();
        $address->update([
            'phone' => $customer['telephone'] ?: null,
            'street' => $customer['site_name'] ?: null,
            'city_id' => $customerCity->id,
        ]);

        return $person;
    }

    public function syncTransactionCustomer(int $stmCustomerId): SteamaCustomer {
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

    /**
     * @param array<string, mixed> $putData
     */
    public function updateSteamaCustomerInfo(SteamaCustomer $stmCustomer, array $putData): SteamaCustomer {
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

    /**
     * @return LengthAwarePaginator<int, SteamaCustomer>|Collection<int, SteamaCustomer>
     */
    public function searchCustomer(string $searchTerm, int $paginate): LengthAwarePaginator|Collection {
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

    /**
     * @param array<string, mixed> $customer
     */
    public function setStmCustomerPaymentPlan(array $customer): void {
        $plan = explode(',', $customer['payment_plan'])[0];

        switch ($plan) {
            case 'Subscription Plan':
                $customerBasisPlan = $this->customerBasisPaymentPlan->newQuery()
                    ->whereHasMorph('paymentPlan', $this->subscriptionPaymentPlan->getMorphClass())->where(
                        'customer_id',
                        $customer['id']
                    )->first();
                if ($customerBasisPlan) {
                    $customerBasisPlan->paymentPlan()->delete();
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
                    $customerBasisPlan->paymentPlan()->delete();
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
                    $customerBasisPlan->paymentPlan()->delete();
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
                    $customerBasisPlan->paymentPlan()->delete();
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
                    $customerBasisPlan->paymentPlan()->delete();
                    $customerBasisPlan->delete();
                }
                $this->setFlatRatePlan($customer);
                break;
        }
    }

    /**
     * @param array<string, mixed> $customer
     */
    public function setFlatRatePlan(array $customer): void {
        $customerBasisPlan = $this->customerBasisPaymentPlan->newQuery()->make([
            'customer_id' => $customer['id'],
        ]);
        $flatRatePlan = $this->flatRatePaymentPlan->newQuery()->create([
            'energy_price' => floatval($customer['energy_price']),
        ]);

        $customerBasisPlan->paymentPlan()->associate($flatRatePlan);
        $customerBasisPlan->save();
    }

    /**
     * @param array<string, mixed> $customer
     */
    public function setPerKwhPlan(array $customer): void {
        $customerBasisPlan = $this->customerBasisPaymentPlan->newQuery()->make([
            'customer_id' => $customer['id'],
        ]);
        $perKwh = $this->perKwhPaymentPlan->newQuery()->create([
            'energy_price' => floatval($customer['energy_price']),
        ]);
        $customerBasisPlan->paymentPlan()->associate($perKwh);
        $customerBasisPlan->save();
    }

    /**
     * @param array<string, mixed> $customer
     */
    public function setMinimumTopUpPlan(array $customer): void {
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

    /**
     * @param array<string, mixed> $customer
     */
    public function setSubscriptionPlan(array $customer): void {
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
            'top_ups_enabled' => (bool) $topUpEnabled,
        ]);
        $customerBasisPlan->paymentPlan()->associate($subscriptionPlan);
        $customerBasisPlan->save();
    }

    /**
     * @param array<string, mixed> $customer
     */
    public function setHybridPlan(array $customer): void {
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

    /**
     * @return array{name: string}
     */
    public function getSteamaCustomerName(int $customerId): array {
        $stmCustomer = $this->customer->newQuery()->with('mpmPerson')->where('customer_id', $customerId)->first();

        return ['name' => $stmCustomer->mpmPerson->name.' '.$stmCustomer->mpmPerson->surname];
    }

    /**
     * @return Builder<SteamaCustomer>
     */
    public function getSteamaCustomersWithAddress(): Builder {
        return $this->customer->newQuery()->with([
            'mpmPerson.addresses',
        ])->whereHas('mpmPerson.addresses', fn ($q) => $q->where('is_primary', 1));
    }

    /**
     * @param array<string, mixed> $steamaCustomer
     */
    private function steamaCustomerHasher(array $steamaCustomer): string {
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

    public function getSteamaCustomerWithPhone(string $phoneNumber): ?SteamaCustomer {
        $person = $this->person::with(['addresses'])
            ->whereHas(
                'addresses',
                static function ($q) use ($phoneNumber) {
                    $q->where('phone', $phoneNumber);
                }
            )->first();

        return $this->customer->newQuery()
            ->with(['site', 'mpmPerson.devices.device'])
            ->where('mpm_customer_id', $person->id)->first();
    }
}
