<?php

use App\Providers\AppServiceProvider;
use App\Providers\HorizonServiceProvider;
use App\Providers\ServicesProvider;
use Inensus\AfricasTalking\Providers\AfricasTalkingServiceProvider;
use Inensus\AngazaSHS\Providers\AngazaSHSServiceProvider;
use Inensus\BulkRegistration\Providers\BulkRegistrationServiceProvider;
use Inensus\CalinMeter\Providers\CalinMeterServiceProvider;
use Inensus\CalinSmartMeter\Providers\CalinSmartMeterServiceProvider;
use Inensus\ChintMeter\Providers\ChintMeterServiceProvider;
use Inensus\DalyBms\Providers\DalyBmsServiceProvider;
use Inensus\GomeLongMeter\Providers\GomeLongMeterServiceProvider;
use Inensus\KelinMeter\Providers\KelinMeterServiceProvider;
use Inensus\MesombPaymentProvider\Providers\MesombServiceProvider;
use Inensus\MicroStarMeter\Providers\MicroStarMeterServiceProvider;
use Inensus\SparkMeter\Providers\SparkMeterServiceProvider;
use Inensus\SteamaMeter\Providers\SteamaMeterServiceProvider;
use Inensus\StronMeter\Providers\StronMeterServiceProvider;
use Inensus\SunKingSHS\Providers\SunKingSHSServiceProvider;
use Inensus\SwiftaPaymentProvider\Providers\SwiftaServiceProvider;
use Inensus\Ticket\Providers\TicketServiceProvider;
use Inensus\ViberMessaging\Providers\ViberMessagingServiceProvider;
use Inensus\VodacomMobileMoney\Providers\VodacomMobileMoneyServiceProvider;
use Inensus\WavecomPaymentProvider\Providers\WavecomPaymentProviderServiceProvider;
use Inensus\WaveMoneyPaymentProvider\Providers\WaveMoneyPaymentProviderServiceProvider;
use Inensus\Prospect\Providers\ProspectServiceProvider;

return [
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
    GomeLongMeterServiceProvider::class,
    KelinMeterServiceProvider::class,
    MesombServiceProvider::class,
    MicroStarMeterServiceProvider::class,
    SparkMeterServiceProvider::class,
    SteamaMeterServiceProvider::class,
    StronMeterServiceProvider::class,
    SunKingSHSServiceProvider::class,
    SwiftaServiceProvider::class,
    TicketServiceProvider::class,
    ViberMessagingServiceProvider::class,
    VodacomMobileMoneyServiceProvider::class,
    WaveMoneyPaymentProviderServiceProvider::class,
    WavecomPaymentProviderServiceProvider::class,
    ProspectServiceProvider::class,
];
