<?php

namespace App\Providers;

use App\Models\Address\Address;
use App\Models\Agent;
use App\Models\AgentBalanceHistory;
use App\Models\AgentCharge;
use App\Models\AgentReceipt;
use App\Models\Country;
use App\Models\Person\Person;
use App\Models\Role\RoleDefinition;
use App\Models\Role\Roles;
use App\Observers\AddressesObserver;
use App\Observers\AgentBalanceHistoryObserver;
use App\Observers\AgentChargeObserver;
use App\Observers\AgentObserver;
use App\Observers\AgentReceiptObserver;
use App\Observers\PersonObserver;
use App\Services\CountryService;
use App\Services\RolesService;
use Illuminate\Support\ServiceProvider;
use Laravel\Horizon\Horizon;

class ServicesProvider extends ServiceProvider {
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void {
        Person::observe(PersonObserver::class);
        Address::observe(AddressesObserver::class);
        Horizon::auth(
            static function ($request) {
                return true;
            }
        );
        AgentBalanceHistory::observe(AgentBalanceHistoryObserver::class);
        AgentReceipt::observe(AgentReceiptObserver::class);
        Agent::observe(AgentObserver::class);
        AgentCharge::observe(AgentChargeObserver::class);
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void {
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
