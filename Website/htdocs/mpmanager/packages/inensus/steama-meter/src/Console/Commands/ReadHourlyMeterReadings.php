<?php


namespace Inensus\SteamaMeter\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Inensus\SteamaMeter\Services\SteamaMeterReadingService;


class ReadHourlyMeterReadings extends AbstractSharedCommand
{
    protected $signature = 'steama-meter:hourlyReadings';
    protected $description = 'Reads hourly meter readings.';

    public function __construct(private SteamaMeterReadingService $steamaMeterReadingService)
    {
        parent::__construct();

    }

   public function runInCompanyScope(): void
    {
        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Steama Meter Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('hourlyReadings command started at ' . $startedAt);
        $this->steamaMeterReadingService->getMeterReadingsThroughHourlyWorkingJob();
        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info("Took " . $totalTime . " seconds.");
        $this->info('#############################');
    }
}