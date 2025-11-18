<?php

namespace App\Providers;

use App\Events\TransactionSuccessfulEvent;
use App\Listeners\TransactionSuccessfulListener;
use App\Misc\LoanDataContainer;
use App\Models\AccessRate\AccessRate;
use App\Models\Address\Address;
use App\Models\Agent;
use App\Models\AgentAssignedAppliances;
use App\Models\AgentCharge;
use App\Models\AgentCommission;
use App\Models\AgentReceipt;
use App\Models\Asset;
use App\Models\AssetRate;
use App\Models\City;
use App\Models\Cluster;
use App\Models\Device;
use App\Models\EBike;
use App\Models\MainSettings;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterTariff;
use App\Models\MiniGrid;
use App\Models\Person\Person;
use App\Models\SolarHomeSystem;
use App\Models\Token;
use App\Models\Transaction\AgentTransaction;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\Transaction;
use App\Models\User;
use App\Policies\MainSettingsPolicy;
use App\Policies\TicketPolicy;
use App\Policies\TransactionPolicy;
use App\Policies\UserPolicy;
use App\Sms\AndroidGateway;
use App\Utils\AccessRatePayer;
use App\Utils\ApplianceInstallmentPayer;
use App\Utils\MinimumPurchaseAmountValidator;
use App\Utils\TariffPriceCalculator;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Inensus\Ticket\Models\Ticket;
use MPM\Transaction\Provider\AgentTransactionProvider;
use MPM\User\Events\UserCreatedEvent;
use MPM\User\UserListener;

class AppServiceProvider extends ServiceProvider {
    /**
     * Bootstrap any application services.
     */
    public function boot(): void {
        // Policies
        Gate::policy(MainSettings::class, MainSettingsPolicy::class);
        Gate::policy(Ticket::class, TicketPolicy::class);
        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        // Maria DB work-around
        Schema::defaultStringLength(191);

        // Rename polymorphic relations
        Relation::morphMap(
            [
                Person::RELATION_NAME => Person::class,
                Manufacturer::RELATION_NAME => Manufacturer::class,
                Transaction::RELATION_NAME => Transaction::class,
                AgentTransaction::RELATION_NAME => AgentTransaction::class,
                AccessRate::RELATION_NAME => AccessRate::class,
                AssetRate::RELATION_NAME => AssetRate::class,
                Cluster::RELATION_NAME => Cluster::class,
                MiniGrid::RELATION_NAME => MiniGrid::class,
                AgentCommission::RELATION_NAME => AgentCommission::class,
                AgentAssignedAppliances::RELATION_NAME => AgentAssignedAppliances::class,
                Agent::RELATION_NAME => Agent::class,
                User::RELATION_NAME => User::class,
                Asset::RELATION_NAME => Asset::class,
                AgentReceipt::RELATION_NAME => AgentReceipt::class,
                AgentCharge::RELATION_NAME => AgentCharge::class,
                MeterTariff::RELATION_NAME => MeterTariff::class,
                ThirdPartyTransaction::RELATION_NAME => ThirdPartyTransaction::class,
                CashTransaction::RELATION_NAME => CashTransaction::class,
                Meter::RELATION_NAME => Meter::class,
                Device::RELATION_NAME => Device::class,
                City::RELATION_NAME => City::class,
                Address::RELATION_NAME => Address::class,
                SolarHomeSystem::RELATION_NAME => SolarHomeSystem::class,
                Token::RELATION_NAME => Token::class,
                EBike::RELATION_NAME => EBike::class,
            ]
        );
    }

    /**
     * Register any application services.
     */
    public function register(): void {
        // Aliases here added for backwards-compatibility
        $this->app->singleton(AndroidGateway::class);
        $this->app->alias(AndroidGateway::class, 'AndroidGateway');
        $this->app->singleton(LoanDataContainer::class);
        $this->app->alias(LoanDataContainer::class, 'LoanDataContainerProvider');
        $this->app->singleton(AgentTransactionProvider::class);
        $this->app->alias(AgentTransactionProvider::class, 'AgentPaymentProvider');

        $this->app->bind(MinimumPurchaseAmountValidator::class);
        $this->app->alias(MinimumPurchaseAmountValidator::class, 'MinimumPurchaseAmountValidator');
        $this->app->bind(TariffPriceCalculator::class);
        $this->app->alias(TariffPriceCalculator::class, 'TariffPriceCalculator');
        $this->app->bind(ApplianceInstallmentPayer::class);
        $this->app->alias(ApplianceInstallmentPayer::class, 'ApplianceInstallmentPayer');
        $this->app->bind(AccessRatePayer::class);
        $this->app->alias(AccessRatePayer::class, 'AccessRatePayer');

        // Register custom MPM Events

        // MPM\User namespace
        Event::listen(
            UserCreatedEvent::class,
            UserListener::class
        );

        // App\Listeners namespace
        // manually register TransactionSuccessfulEvent to listener.
        // because TransactionSuccessfulEvent is also registered in SparkMeter namespace.
        Event::listen(
            TransactionSuccessfulEvent::class,
            TransactionSuccessfulListener::class
        );
    }
}
