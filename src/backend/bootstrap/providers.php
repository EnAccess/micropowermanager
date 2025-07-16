<?php

return [
    /*
    * Application Service Providers...
    */
    App\Providers\AppServiceProvider::class,

    App\Providers\ServicesProvider::class, // for helper services
    Inensus\Ticket\Providers\TicketServiceProvider::class,
    Inensus\SparkMeter\Providers\SparkMeterServiceProvider::class,
    Inensus\SteamaMeter\Providers\SteamaMeterServiceProvider::class,
    Inensus\KelinMeter\Providers\KelinMeterServiceProvider::class,
    Inensus\CalinMeter\Providers\CalinMeterServiceProvider::class,
    Inensus\StronMeter\Providers\StronMeterServiceProvider::class,
    Inensus\CalinSmartMeter\Providers\CalinSmartMeterServiceProvider::class,
    Inensus\SwiftaPaymentProvider\Providers\SwiftaServiceProvider::class,
    Inensus\MesombPaymentProvider\Providers\MesombServiceProvider::class,
    Inensus\BulkRegistration\Providers\BulkRegistrationServiceProvider::class,
    Inensus\ViberMessaging\Providers\ViberMessagingServiceProvider::class,
    Inensus\WaveMoneyPaymentProvider\Providers\WaveMoneyPaymentProviderServiceProvider::class,
    Inensus\MicroStarMeter\Providers\MicroStarMeterServiceProvider::class,
    Inensus\SunKingSHS\Providers\SunKingSHSServiceProvider::class,
    Inensus\GomeLongMeter\Providers\GomeLongMeterServiceProvider::class,
    Inensus\WavecomPaymentProvider\Providers\WavecomPaymentProviderServiceProvider::class,
    Inensus\AngazaSHS\Providers\AngazaSHSServiceProvider::class,
    Inensus\DalyBms\Providers\DalyBmsServiceProvider::class,
    Inensus\AfricasTalking\Providers\AfricasTalkingServiceProvider::class,
    Inensus\VodacomMobileMoney\Providers\VodacomMobileMoneyServiceProvider::class,
    Inensus\ChintMeter\Providers\ChintMeterServiceProvider::class,
];
