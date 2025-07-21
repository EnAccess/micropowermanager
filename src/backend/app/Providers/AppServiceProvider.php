<?php

namespace App\Providers;

use App\Helpers\MailHelper;
use App\Helpers\MailHelperInterface;
use App\Helpers\MailHelperMock;
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
use App\Models\MaintenanceUsers;
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
use App\Sms\AndroidGateway;
use App\Utils\AccessRatePayer;
use App\Utils\ApplianceInstallmentPayer;
use App\Utils\MinimumPurchaseAmountValidator;
use App\Utils\TariffPriceCalculator;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use MPM\Transaction\Provider\AgentTransactionProvider;
use MPM\User\Events\UserCreatedEvent;
use MPM\User\UserListener;

class AppServiceProvider extends ServiceProvider {
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {
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
                MaintenanceUsers::RELATION_NAME => MaintenanceUsers::class,
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
     *
     * @return void
     */
    public function register(): void {
        if ($this->app->environment('development') || $this->app->environment('local')) {
            $this->app->singleton(MailHelperInterface::class, MailHelperMock::class);
        } else {
            $this->app->singleton(MailHelperInterface::class, MailHelper::class);
        }

        $this->app->singleton('AndroidGateway', AndroidGateway::class);
        $this->app->singleton('LoanDataContainerProvider', LoanDataContainer::class);
        $this->app->singleton('AgentPaymentProvider', AgentTransactionProvider::class);
        $this->app->bind('MinimumPurchaseAmountValidator', MinimumPurchaseAmountValidator::class);
        $this->app->bind('TariffPriceCalculator', TariffPriceCalculator::class);
        $this->app->bind('ApplianceInstallmentPayer', ApplianceInstallmentPayer::class);
        $this->app->bind('AccessRatePayer', AccessRatePayer::class);

        // Register custom MPM Events

        // MPM\User namespace
        Event::listen(
            UserCreatedEvent::class,
            UserListener::class
        );
    }
}
