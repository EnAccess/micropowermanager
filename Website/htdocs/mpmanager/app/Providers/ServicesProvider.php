<?php

namespace App\Providers;

use App\Models\AgentCharge;
use App\Observers\AgentChargeObserver;
use App\Services\CountryService;
use App\Services\RolesService;
use App\Models\Address\Address;
use App\Models\Agent;
use App\Models\AgentBalanceHistory;
use App\Models\AgentReceipt;
use App\Models\AgentSoldAppliance;
use App\Models\Battery;
use App\Models\Country;
use App\Models\Meter\MeterParameter;
use App\Models\MiniGrid;
use App\Models\Person\Person;
use App\Models\PV;
use App\Models\Role\RoleDefinition;
use App\Models\Role\Roles;
use App\Models\Solar;
use App\Observers\AddressesObserver;
use App\Observers\AgentBalanceHistoryObserver;
use App\Observers\AgentObserver;
use App\Observers\AgentReceiptObserver;
use App\Observers\AgentSoldApplianceObserver;
use App\Observers\BatteryObserver;
use App\Observers\MeterParameterObserver;
use App\Observers\PersonObserver;
use App\Observers\PVObserver;
use App\Observers\SolarObserver;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class ServicesProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        Person::observe(PersonObserver::class);
        Address::observe(AddressesObserver::class);
        MeterParameter::observe(MeterParameterObserver::class);
        PV::observe(PVObserver::class);
        Battery::observe(BatteryObserver::class);
        Horizon::auth(
            static function ($request) {
                return true;
            }
        );
        Solar::observe(SolarObserver::class);
        AgentBalanceHistory::observe(AgentBalanceHistoryObserver::class);
        AgentSoldAppliance::observe(AgentSoldApplianceObserver::class);
        AgentReceipt::observe(AgentReceiptObserver::class);
        Agent::observe(AgentObserver::class);
        AgentCharge::observe(AgentChargeObserver::class);
    }


    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {

        $this->app->bind(
            RolesService::class,
            function ($app) {
                return new RolesService($this->app->make(Roles::class), $this->app->make(RoleDefinition::class));
            }
        );

        $this->app->bind(
            CountryService::class,
            function ($app) {
                return new CountryService($this->app->make(Country::class));
            }
        );
    }
}
