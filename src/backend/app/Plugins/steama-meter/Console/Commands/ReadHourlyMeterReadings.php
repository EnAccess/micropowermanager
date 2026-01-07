<?php

namespace Inensus\SteamaMeter\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Traits\ScheduledPluginCommand;
use Carbon\Carbon;
use Inensus\SteamaMeter\Services\SteamaMeterReadingService;

class ReadHourlyMeterReadings extends AbstractSharedCommand {
    use ScheduledPluginCommand;
    public const MPM_PLUGIN_ID = 2;

    protected $signature = 'steama-meter:hourlyReadings';
    protected $description = 'Reads hourly meter readings.';

    public function __construct(private SteamaMeterReadingService $steamaMeterReadingService) {
        parent::__construct();
    }

    public function handle(): void {
        if (!$this->checkForPluginStatusIsActive(self::MPM_PLUGIN_ID)) {
            return;
        }

        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Steama Meter Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('hourlyReadings command started at '.$startedAt);
        $this->steamaMeterReadingService->getMeterReadingsThroughHourlyWorkingJob();
        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info('Took '.$totalTime.' seconds.');
        $this->info('#############################');
    }
}
