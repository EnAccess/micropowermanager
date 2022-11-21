<?php

namespace App\Providers;

use App\Helpers\MailHelper;
use App\Utils\MinimumPurchaseAmountValidator;
use App\ManufacturerApi\CalinApi;
use App\ManufacturerApi\CalinSmartApi;
use App\Misc\LoanDataContainer;
use App\Models\AccessRate\AccessRate;
use App\Models\Agent;
use App\Models\AgentAssignedAppliances;
use App\Models\AgentCharge;
use App\Models\AgentCommission;
use App\Models\AgentReceipt;
use App\Models\AssetRate;
use App\Models\AssetType;
use App\Models\Cluster;
use App\Models\Manufacturer;
use App\Models\Meter\MeterParameter;
use App\Models\Meter\MeterTariff;
use App\Models\Meter\MeterToken;
use App\Models\MiniGrid;
use App\Models\Person\Person;
use App\Models\SmsAndroidSetting;
use App\Models\Transaction\CashTransaction;
use App\Models\Transaction\ThirdPartyTransaction;
use App\Models\Transaction\Transaction;
use App\Models\Transaction\VodacomTransaction;
use App\Models\User;
use App\Services\FirebaseService;
use App\Services\SmsAndroidSettingService;
use App\Sms\AndroidGateway;
use App\Transaction\AgentTransaction;
use App\Transaction\AirtelTransaction;
use App\Utils\TariffPriceCalculator;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use PHPMailer\PHPMailer\PHPMailer;

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
                'agent_transaction' => \App\Models\Transaction\AgentTransaction::class,
                'airtel_transaction' => \App\Models\Transaction\AirtelTransaction::class,
                'vodacom_transaction' => VodacomTransaction::class,
                'access_rate' => AccessRate::class,
                'asset_loan' => AssetRate::class,
                'cluster' => Cluster::class,
                'mini-grid' => MiniGrid::class,
                'agent_commission' => AgentCommission::class,
                'agent_appliance' => AgentAssignedAppliances::class,
                'agent' => Agent::class,
                'admin' => User::class,
                'appliance' => AssetType::class,
                'agent_receipt' => AgentReceipt::class,
                'agent_charge' => AgentCharge::class,
                'meter_tariff' => MeterTariff::class,
                'third_party_transaction' => ThirdPartyTransaction::class,
                'cash_transaction' => CashTransaction::class
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

        $this->app->bind('AndroidSettingsService', function ($app) {
            return new SmsAndroidSettingService($app->make(SmsAndroidSetting::class));
        });

        $this->app->singleton(
            'MailProvider',
            static function ($app) {
                return new MailHelper(new PHPMailer());
            }
        );

        /**
 * Use the current sms provider
*/
        //$this->app->singleton('SmsProvider', function ($app) {
        //   return new \App\Sms\Bongo();
        //});
        /*Use android device as sms gateway*/
        $this->app->singleton(
            'SmsProvider',
            static function ($app) {
                return new AndroidGateway();
            }
        );
        $this->app->singleton(
            'LoanDataContainerProvider',
            static function ($app) {
                return new LoanDataContainer();
            }
        );
        $this->app->singleton(
            'VodacomPaymentProvider',
            static function () {
                return new \App\Transaction\VodacomTransaction();
            }
        );

        $this->app->singleton(
            'AirtelPaymentProvider',
            static function () {
                return new AirtelTransaction(
                    new \App\Models\Transaction\AirtelTransaction(),
                    new Transaction()
                );
            }
        );
        $this->app->singleton(
            'AgentPaymentProvider',
            static function () {
                return new AgentTransaction(
                    new \App\Models\Transaction\AgentTransaction(),
                    new Transaction(),
                    new FirebaseService()
                );
            }
        );
        $this->app->bind('MinimumPurchaseAmountValidator', MinimumPurchaseAmountValidator::class);
        $this->app->bind('TariffPriceCalculator', TariffPriceCalculator::class);
    }
}
