<?php

use App\Plugins\AfricasTalking\Providers\AfricasTalkingServiceProvider;
use App\Plugins\AngazaSHS\Providers\AngazaSHSServiceProvider;
use App\Plugins\BulkRegistration\Providers\BulkRegistrationServiceProvider;
use App\Plugins\CalinMeter\Providers\CalinMeterServiceProvider;
use App\Plugins\CalinSmartMeter\Providers\CalinSmartMeterServiceProvider;
use App\Plugins\ChintMeter\Providers\ChintMeterServiceProvider;
use App\Plugins\DalyBms\Providers\DalyBmsServiceProvider;
use App\Plugins\DemoMeterManufacturer\Providers\DemoMeterManufacturerServiceProvider;
use App\Plugins\DemoShsManufacturer\Providers\DemoShsManufacturerServiceProvider;
use App\Plugins\EcreeeETender\Providers\EcreeeETenderServiceProvider;
use App\Plugins\GomeLongMeter\Providers\GomeLongMeterServiceProvider;
use App\Plugins\KelinMeter\Providers\KelinMeterServiceProvider;
use App\Plugins\MesombPaymentProvider\Providers\MesombServiceProvider;
use App\Plugins\MicroStarMeter\Providers\MicroStarMeterServiceProvider;
use App\Plugins\OdysseyDataExport\Providers\OdysseyDataExportServiceProvider;
use App\Plugins\PaystackPaymentProvider\Providers\PaystackPaymentProviderServiceProvider;
use App\Plugins\Prospect\Providers\ProspectServiceProvider;
use App\Plugins\SparkMeter\Providers\SparkMeterServiceProvider;
use App\Plugins\SparkShs\Providers\SparkShsServiceProvider;
use App\Plugins\SteamaMeter\Providers\SteamaMeterServiceProvider;
use App\Plugins\StronMeter\Providers\StronMeterServiceProvider;
use App\Plugins\SunKingSHS\Providers\SunKingSHSServiceProvider;
use App\Plugins\SwiftaPaymentProvider\Providers\SwiftaServiceProvider;
use App\Plugins\TextbeeSmsGateway\Providers\TextbeeSmsGatewayServiceProvider;
use App\Plugins\ViberMessaging\Providers\ViberMessagingServiceProvider;
use App\Plugins\VodacomMobileMoney\Providers\VodacomMobileMoneyServiceProvider;
use App\Plugins\WavecomPaymentProvider\Providers\WavecomPaymentProviderServiceProvider;
use App\Plugins\WaveMoneyPaymentProvider\Providers\WaveMoneyPaymentProviderServiceProvider;
use App\Providers\ApiKeyServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\ServicesProvider;

return [
    ApiKeyServiceProvider::class,
    AppServiceProvider::class,
    HorizonServiceProvider::class,
    ServicesProvider::class,
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
    SparkShsServiceProvider::class,
];
