<?php

namespace Inensus\KelinMeter\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Traits\ScheduledPluginCommand;
use Carbon\Carbon;
use Inensus\KelinMeter\Services\KelinCredentialService;
use Inensus\KelinMeter\Services\MinutelyConsumptionService;

class ReadMinutelyMeterConsumptions extends AbstractSharedCommand {
    use ScheduledPluginCommand;
    public const MPM_PLUGIN_ID = 5;

    protected $signature = 'kelin-meter:read-minutely-consumptions';
    protected $description = 'Reads daily meter consumptions.';

    public function __construct(
        private MinutelyConsumptionService $minutelyConsumptionService,
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
        $this->info('read-minutely-consumptions command started at '.$startedAt);
        if ($credentials && $credentials->is_authenticated == 1) {
            $this->minutelyConsumptionService->getMinutelyDataFromAPI();
        } else {
            $this->info('# Kelin credentials not authenticated #');
        }
        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info('Took '.$totalTime.' seconds.');
        $this->info('#############################');
    }
}
