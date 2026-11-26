<?php

namespace App\Plugins\KelinMeter\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Plugins\KelinMeter\Providers\KelinMeterServiceProvider;
use App\Plugins\KelinMeter\Services\KelinCredentialService;
use App\Traits\ScheduledPluginCommand;
use Carbon\Carbon;

class AccessTokenRefresher extends AbstractSharedCommand {
    use ScheduledPluginCommand;
    protected $signature = 'kelin-meter:access-token-refresher';
    protected $description = 'Refreshes access token per each one hour.';

    public function __construct(private KelinCredentialService $credentialService) {
        parent::__construct();
    }

    public function handle(): void {
        if (!$this->checkForPluginStatusIsActive(KelinMeterServiceProvider::SLUG)) {
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
