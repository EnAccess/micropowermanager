<?php

use App\Providers\ApiKeyServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\ServicesProvider;
use App\Providers\TelescopeServiceProvider;
use Inensus\AfricasTalking\Providers\AfricasTalkingServiceProvider;
use Inensus\AngazaSHS\Providers\AngazaSHSServiceProvider;
use Inensus\BulkRegistration\Providers\BulkRegistrationServiceProvider;
use Inensus\CalinMeter\Providers\CalinMeterServiceProvider;
use Inensus\CalinSmartMeter\Providers\CalinSmartMeterServiceProvider;
use Inensus\ChintMeter\Providers\ChintMeterServiceProvider;
use Inensus\DalyBms\Providers\DalyBmsServiceProvider;
use Inensus\DemoMeterManufacturer\Providers\DemoMeterManufacturerServiceProvider;
use Inensus\DemoShsManufacturer\Providers\DemoShsManufacturerServiceProvider;
use Inensus\EcreeeETender\Providers\EcreeeETenderServiceProvider;
use Inensus\GomeLongMeter\Providers\GomeLongMeterServiceProvider;
use Inensus\KelinMeter\Providers\KelinMeterServiceProvider;
use Inensus\MesombPaymentProvider\Providers\MesombServiceProvider;
use Inensus\MicroStarMeter\Providers\MicroStarMeterServiceProvider;
use Inensus\OdysseyDataExport\Providers\OdysseyDataExportServiceProvider;
use Inensus\PaystackPaymentProvider\Providers\PaystackPaymentProviderServiceProvider;
use Inensus\Prospect\Providers\ProspectServiceProvider;
use Inensus\SparkMeter\Providers\SparkMeterServiceProvider;
use Inensus\SteamaMeter\Providers\SteamaMeterServiceProvider;
use Inensus\StronMeter\Providers\StronMeterServiceProvider;
use Inensus\SunKingSHS\Providers\SunKingSHSServiceProvider;
use Inensus\SwiftaPaymentProvider\Providers\SwiftaServiceProvider;
use Inensus\TextbeeSmsGateway\Providers\TextbeeSmsGatewayServiceProvider;
use Inensus\ViberMessaging\Providers\ViberMessagingServiceProvider;
use Inensus\VodacomMobileMoney\Providers\VodacomMobileMoneyServiceProvider;
use Inensus\WavecomPaymentProvider\Providers\WavecomPaymentProviderServiceProvider;
use Inensus\WaveMoneyPaymentProvider\Providers\WaveMoneyPaymentProviderServiceProvider;

return [
    ApiKeyServiceProvider::class,
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    ServicesProvider::class,
    TelescopeServiceProvider::class,
    AfricasTalkingServiceProvider::class,
    AngazaSHSServiceProvider::class,
    BulkRegistrationServiceProvider::class,
    CalinMeterServiceProvider::class,
    CalinSmartMeterServiceProvider::class,
    ChintMeterServiceProvider::class,
    DalyBmsServiceProvider::class,
    DemoMeterManufacturerServiceProvider::class,
    DemoShsManufacturerServiceProvider::class,
    GomeLongMeterServiceProvider::class,
    KelinMeterServiceProvider::class,
    MesombServiceProvider::class,
    MicroStarMeterServiceProvider::class,
    OdysseyDataExportServiceProvider::class,
    PaystackPaymentProviderServiceProvider::class,
    ProspectServiceProvider::class,
    SparkMeterServiceProvider::class,
    SteamaMeterServiceProvider::class,
    StronMeterServiceProvider::class,
    SunKingSHSServiceProvider::class,
    SwiftaServiceProvider::class,
    TextbeeSmsGatewayServiceProvider::class,
    ViberMessagingServiceProvider::class,
    VodacomMobileMoneyServiceProvider::class,
    WaveMoneyPaymentProviderServiceProvider::class,
    WavecomPaymentProviderServiceProvider::class,
    EcreeeETenderServiceProvider::class,
];
