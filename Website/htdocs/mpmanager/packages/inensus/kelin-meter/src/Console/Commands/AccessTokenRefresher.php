<?php


namespace Inensus\KelinMeter\Console\Commands;


use App\Console\Commands\AbstractSharedCommand;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Inensus\KelinMeter\Services\KelinCredentialService;

class AccessTokenRefresher extends AbstractSharedCommand
{
    protected $signature = 'kelin-meter:access-token-refresher';
    protected $description = 'Refreshes access token per each one hour.';

    public function __construct(private KelinCredentialService $credentialService)
    {
        parent::__construct();

    }

   public function runInCompanyScope(): void
    {
        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Kelin Meter Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('access-token-refresher command started at ' . $startedAt);
        $this->credentialService->refreshAccessToken();
        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info("Took " . $totalTime . " seconds.");
        $this->info('#############################');
    }
}