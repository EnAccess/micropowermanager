<?php

namespace App\Console;

use App\Jobs\SocialTariffPiggyBankManager;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Inensus\CalinMeter\Console\Commands\InstallPackage as InstallCalinMeterPackage;
use Inensus\SparkMeter\Console\Commands\InstallSparkMeterPackage;
use Inensus\CalinSmartMeter\Console\Commands\InstallPackage as InstallCalinSmartMeterPackage;
use Inensus\KelinMeter\Console\Commands\InstallPackage as InstallKelinMeterPackage;
use Inensus\StronMeter\Console\Commands\InstallPackage as InstallStronMeterPackage;
use Inensus\SteamaMeter\Console\Commands\InstallPackage as InstallStemacoMeterPackage;
use Inensus\SwiftaPaymentProvider\Console\Commands\InstallPackage as InstallSwiftaPaymentProviderPackage;
use Inensus\MesombPaymentProvider\Console\Commands\InstallPackage as InstallMsombPaymentProviderPackage;
use Inensus\BulkRegistration\Console\Commands\InstallPackage as InstallBulkRegistrationPackage;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [

        Commands\AddMeterAddress::class,
        InstallSparkMeterPackage::class,
        InstallCalinMeterPackage::class,
        InstallCalinSmartMeterPackage::class,
        InstallKelinMeterPackage::class,
        InstallStronMeterPackage::class,
        InstallStemacoMeterPackage::class,
        InstallSwiftaPaymentProviderPackage::class,
        InstallMsombPaymentProviderPackage::class,
        InstallBulkRegistrationPackage::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param Schedule $schedule
     *
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
/*        $schedule->command('accessrate:check')->daily();
        $schedule->command('asset-rate:check')->daily();
        $schedule->command('calinMeters:readOnline')->dailyAt('04:00');
        $schedule->command('reports:city-revenue weekly')->weeklyOn(1, '3:00');
        $schedule->command('reports:city-revenue monthly')->monthlyOn(1, '3:00');
        $schedule->command('reports:outsource')->monthlyOn(1, '3:30');
        $schedule->command('update:cachedClustersDashboardData')->everyFifteenMinutes();
        $schedule->job(new SocialTariffPiggyBankManager())->daily();*/
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        include base_path('routes/console.php');
    }
}
