<?php

namespace App\Providers;

use App\Models\Address\Address;
use App\Models\Agent;
use App\Models\AgentBalanceHistory;
use App\Models\AgentCharge;
use App\Models\AgentReceipt;
use App\Models\Country;
use App\Models\Device;
use App\Models\EBike;
use App\Models\Meter\Meter;
use App\Models\Person\Person;
use App\Models\SolarHomeSystem;
use App\Observers\AddressesObserver;
use App\Observers\AgentBalanceHistoryObserver;
use App\Observers\AgentChargeObserver;
use App\Observers\AgentObserver;
use App\Observers\AgentReceiptObserver;
use App\Observers\DeviceManufacturerObserver;
use App\Observers\DeviceObserver;
use App\Observers\PersonObserver;
use App\Services\CountryService;
use Illuminate\Support\ServiceProvider;

class ServicesProvider extends ServiceProvider {
    /**
     * Bootstrap services.
     */
    public function boot(): void {
        Person::observe(PersonObserver::class);
        Address::observe(AddressesObserver::class);
        AgentBalanceHistory::observe(AgentBalanceHistoryObserver::class);
        AgentReceipt::observe(AgentReceiptObserver::class);
        Agent::observe(AgentObserver::class);
        AgentCharge::observe(AgentChargeObserver::class);
        Device::observe(DeviceObserver::class);
        Meter::observe(DeviceManufacturerObserver::class);
        SolarHomeSystem::observe(DeviceManufacturerObserver::class);
        EBike::observe(DeviceManufacturerObserver::class);
    }

    /**
     * Register services.
     */
    public function register(): void {
        $this->app->bind(
            CountryService::class,
            fn ($app): CountryService => new CountryService($this->app->make(Country::class))
        );
    }
}
