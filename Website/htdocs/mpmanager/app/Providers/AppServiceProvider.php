<?php

namespace App\Providers;

use App\Helpers\MailHelper;
use App\Helpers\MailHelperInterface;
use App\Helpers\MailHelperMock;
use App\Misc\LoanDataContainer;
use App\Models\AccessRate\AccessRate;
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
use App\Models\MaintenanceUsers;
use App\Models\Manufacturer;
use App\Models\Meter\Meter;
use App\Models\Meter\MeterParameter;
use App\Models\Meter\MeterTariff;
use App\Models\Meter\MeterToken;
use App\Models\MiniGrid;
use App\Models\Person\Person;
use App\Models\Transaction\AirtelTransaction;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\Transaction;
use App\Models\User;
use MPM\Transaction\Provider\AgentTransaction;
use MPM\Transaction\Provider\AirtelVoltTerra;
use MPM\Transaction\Provider\VodacomTransaction;
use App\Sms\AndroidGateway;
use App\Utils\AccessRatePayer;
use App\Utils\ApplianceInstallmentPayer;
use App\Utils\MinimumPurchaseAmountValidator;
use App\Utils\TariffPriceCalculator;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //Maria DB work-around
        Schema::defaultStringLength(191);

        //Rename polymorphic relations
        Relation::morphMap(
            [
                'person' => Person::class,
                'manufacturer' => Manufacturer::class,
                'meter_parameter' => MeterParameter::class,
                'token' => MeterToken::class,
                'transaction' => Transaction::class,
                \App\Models\Transaction\AgentTransaction::RELATION_NAME => \App\Models\Transaction\AgentTransaction::class,
                AirtelTransaction::RELATION_NAME => AirtelTransaction::class,
                \App\Models\Transaction\VodacomTransaction::RELATION_NAME => \App\Models\Transaction\VodacomTransaction::class,
                'access_rate' => AccessRate::class,
                'asset_loan' => AssetRate::class,
                'cluster' => Cluster::class,
                'mini-grid' => MiniGrid::class,
                'agent_commission' => AgentCommission::class,
                'agent_appliance' => AgentAssignedAppliances::class,
                'agent' => Agent::class,
                'admin' => User::class,
                'appliance' => Asset::class,
                'agent_receipt' => AgentReceipt::class,
                'agent_charge' => AgentCharge::class,
                'meter_tariff' => MeterTariff::class,
                'third_party_transaction' => ThirdPartyTransaction::class,
                'cash_transaction' => CashTransaction::class,
                'maintenance_user' => MaintenanceUsers::class,
                'meter' => Meter::class,
                'device' => Device::class,
                'city' => City::class,
                'address' => \App\Models\Address\Address::class,
            ]
        );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(): void
    {
        if ($this->app->environment('development') || $this->app->environment('local')) {
            $this->app->singleton(MailHelperInterface::class, MailHelperMock::class);
        } else {
            $this->app->singleton(MailHelperInterface::class, MailHelper::class);
        }

        $this->app->singleton('AndroidGateway', AndroidGateway::class);
        $this->app->singleton('LoanDataContainerProvider', LoanDataContainer::class);
        $this->app->singleton('AgentPaymentProvider', AgentTransaction::class);
        $this->app->singleton('AirtelVoltTerra', AirtelVoltTerra::class); // workaround until airtel problem
        $this->app->singleton('VodacomPaymentProvider', VodacomTransaction::class);
        $this->app->bind('MinimumPurchaseAmountValidator', MinimumPurchaseAmountValidator::class);
        $this->app->bind('TariffPriceCalculator', TariffPriceCalculator::class);
        $this->app->bind('ApplianceInstallmentPayer', ApplianceInstallmentPayer::class);
        $this->app->bind('AccessRatePayer', AccessRatePayer::class);
    }
}
