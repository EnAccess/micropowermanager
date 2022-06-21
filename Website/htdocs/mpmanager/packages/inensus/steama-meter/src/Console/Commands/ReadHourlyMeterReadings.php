<?php


namespace Inensus\SteamaMeter\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Inensus\SteamaMeter\Services\SteamaMeterReadingService;


class ReadHourlyMeterReadings extends Command
{
    protected $signature = 'steama-meter:hourlyReadings';
    protected $description = 'Reads hourly meter readings.';

    private $steamaMeterReadingService;

    public function __construct(SteamaMeterReadingService $steamaMeterReadingService)
    {
        parent::__construct();
        $this->steamaMeterReadingService = $steamaMeterReadingService;
    }

    public function handle()
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