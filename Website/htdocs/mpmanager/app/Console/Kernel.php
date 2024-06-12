<?php

namespace App\Console;

use app\Console\Commands\MailApplianceDebtsCommand;
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
use Inensus\ViberMessaging\Console\Commands\InstallPackage as InstallViberMessagingPackage;
use Inensus\WaveMoneyPaymentProvider\Console\Commands\InstallPackage as InstallWaveMoneyPaymentProviderPackage;
use Inensus\MicroStarMeter\Console\Commands\InstallPackage as InstallMicroStarMeterPackage;
use Inensus\SunKingSHS\Console\Commands\InstallPackage as InstallSunKingSHSPackage;
use Inensus\GomeLongMeter\Console\Commands\InstallPackage as InstallGomeLongMeterPackage;
use Inensus\WavecomPaymentProvider\Console\Commands\InstallPackage as InstallWaveComPackage;
use Inensus\AngazaSHS\Console\Commands\InstallPackage as InstallAngazaSHSPackage;
use Inensus\DalyBms\Console\Commands\InstallPackage as InstallDalyBmsPackage;
use Inensus\AirtelPaymentProvider\Console\Commands\InstallPackage as InstallAirtelPaymentProviderPackage;

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
        InstallViberMessagingPackage::class,
        InstallWaveMoneyPaymentProviderPackage::class,
        InstallMicroStarMeterPackage::class,
        InstallSunKingSHSPackage::class,
        InstallGomeLongMeterPackage::class,
        InstallWaveComPackage::class,
        InstallAngazaSHSPackage::class,
        InstallDalyBmsPackage::class,
        InstallAirtelPaymentProviderPackage::class,
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
        $schedule->command('reports:city-revenue weekly')->weeklyOn(1, '3:00');
        $schedule->command('reports:city-revenue monthly')->monthlyOn(1, '3:00');
        $schedule->command('reports:outsource')->monthlyOn(1, '3:30');
        $schedule->command('sms:resend-rejected 5')->everyMinute();
        $schedule->command('update:cachedClustersDashboardData')->everyFifteenMinutes();
        $schedule->command('dummy:create-data 25 --company-id=11 --type=transaction')->dailyAt('00:00');
        $schedule->command('dummy:create-data 2 --company-id=11 --type=ticket')->dailyAt('00:00');
        $schedule->command('asset-rate:check')->dailyAt('00:00');
        // will run on the last day of the month
        $schedule->command(MailApplianceDebtsCommand::class)->weeklyOn(1, '6:00');
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
