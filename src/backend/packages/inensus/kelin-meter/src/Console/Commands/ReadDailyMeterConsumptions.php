<?php

namespace Inensus\KelinMeter\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Traits\ScheduledPluginCommand;
use Carbon\Carbon;
use Inensus\KelinMeter\Services\DailyConsumptionService;
use Inensus\KelinMeter\Services\KelinCredentialService;

class ReadDailyMeterConsumptions extends AbstractSharedCommand {
    use ScheduledPluginCommand;
    public const MPM_PLUGIN_ID = 5;

    protected $signature = 'kelin-meter:read-daily-consumptions';
    protected $description = 'Reads daily meter consumptions.';

    public function __construct(
        private DailyConsumptionService $dailyConsumptionService,
        private KelinCredentialService $credentialService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        if (!$this->checkForPluginStatusIsActive(self::MPM_PLUGIN_ID)) {
            return;
        }

        $credentials = $this->credentialService->getCredentials();
        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Kelin Meter Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('read-daily-consumptions command started at '.$startedAt);

        if ($credentials && $credentials->is_authenticated == 1) {
            $this->dailyConsumptionService->getDailyDataFromAPI();
        } else {
            $this->info('# Kelin credentials not authenticated #');
        }
        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info('Took '.$totalTime.' seconds.');
        $this->info('#############################');
    }
}
