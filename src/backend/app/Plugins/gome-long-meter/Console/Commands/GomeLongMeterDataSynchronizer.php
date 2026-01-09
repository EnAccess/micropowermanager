<?php

namespace Inensus\GomeLongMeter\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use Carbon\Carbon;
use Inensus\GomeLongMeter\Exceptions\CronJobException;
use Inensus\GomeLongMeter\Services\GomeLongTariffService;

class GomeLongMeterDataSynchronizer extends AbstractSharedCommand {
    protected $signature = 'gome-long-meter:dataSync';
    protected $description = 'Synchronize data that needs to be updated from GomeLong Meter Meter.';

    public function __construct(
        private GomeLongTariffService $gomeLongTariffService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# GomeLong Meter Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('dataSync command started at '.$startedAt);
        try {
            $this->gomeLongTariffService->sync();
        } catch (CronJobException $e) {
            $this->warn('dataSync command is failed. message => '.$e->getMessage());
        }
        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info('Took '.$totalTime.' seconds.');
        $this->info('#############################');
    }
}
