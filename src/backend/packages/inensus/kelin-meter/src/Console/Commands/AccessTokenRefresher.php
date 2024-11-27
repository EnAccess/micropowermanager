<?php

namespace Inensus\KelinMeter\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Traits\ScheduledPluginCommand;
use Carbon\Carbon;
use Inensus\KelinMeter\Services\KelinCredentialService;

class AccessTokenRefresher extends AbstractSharedCommand {
    use ScheduledPluginCommand;
    public const MPM_PLUGIN_ID = 5;
    protected $signature = 'kelin-meter:access-token-refresher';
    protected $description = 'Refreshes access token per each one hour.';

    public function __construct(private KelinCredentialService $credentialService) {
        parent::__construct();
    }

    public function handle(): void {
        if (!$this->checkForPluginStatusIsActive(self::MPM_PLUGIN_ID)) {
            return;
        }

        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Kelin Meter Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('access-token-refresher command started at '.$startedAt);
        $this->credentialService->refreshAccessToken();
        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info('Took '.$totalTime.' seconds.');
        $this->info('#############################');
    }
}
