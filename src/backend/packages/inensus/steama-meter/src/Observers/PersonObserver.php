<?php

namespace Inensus\SteamaMeter\Observers;

use App\Models\Person\Person;
use Inensus\SteamaMeter\Helpers\ApiHelpers;
use Inensus\SteamaMeter\Http\Clients\SteamaMeterApiClient;
use Inensus\SteamaMeter\Models\SteamaCustomer;
use Inensus\SteamaMeter\Services\SteamaCustomerService;
use Inensus\SteamaMeter\Services\SteamaMeterService;

class PersonObserver {
    private $apiHelpers;
    private $stmCustomerService;
    private $stmMeterService;
    private $stmCustomer;
    private $person;
    private $steamaApi;

    public function __construct(
        ApiHelpers $apiHelpers,
        SteamaMeterService $stmMeterService,
        SteamaCustomerService $stmCustomerService,
        Person $person,
        SteamaCustomer $steamaCustomer,
        SteamaMeterApiClient $steamaApi,
    ) {
        $this->apiHelpers = $apiHelpers;
        $this->stmCustomerService = $stmCustomerService;
        $this->stmMeterService = $stmMeterService;
        $this->person = $person;
        $this->stmCustomer = $steamaCustomer;
        $this->steamaApi = $steamaApi;
    }

    public function updated(Person $person) {
        $stmCustomer = $this->stmCustomer->newQuery()->with('site')->where('mpm_customer_id', $person->id)->first();

        if ($stmCustomer) {
            $personId = $person->id;
            $customer = $this->person->newQuery()->with([
                'meters.tariff',
                'meters.geo',
                'meters.meter',
                'addresses' => function ($q) {
                    return $q->where('is_primary', 1);
                },
            ])->where('id', $personId)->first();

            $customerData = [
                'id' => $stmCustomer->customer_id,
                'first_name' => $person->name,
                'last_name' => $person->surname,
                'telephone' => $customer->addresses[0]->phone,
            ];
            $this->stmCustomerService->updateSteamaCustomerInfo($stmCustomer, $customerData);
        }
    }
}
