<?php


namespace Inensus\KelinMeter\Console\Commands;


use Carbon\Carbon;
use Illuminate\Console\Command;
use Inensus\KelinMeter\Services\DailyConsumptionService;
use Inensus\KelinMeter\Services\KelinCredentialService;
use Inensus\KelinMeter\Services\KelinMeterConsumptionService;


class ReadDailyMeterConsumptions extends Command
{
    protected $signature = 'kelin-meter:read-daily-consumptions';
    protected $description = 'Reads daily meter consumptions.';

    private $dailyConsumptionService;
    private $credentialService;

    public function __construct(
        DailyConsumptionService $dailyConsumptionService,
        KelinCredentialService $credentialService
    ) {
        parent::__construct();
        $this->dailyConsumptionService = $dailyConsumptionService;
        $this->credentialService = $credentialService;
    }

    public function handle()
    {
        $credentials = $this->credentialService->getCredentials();
        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Kelin Meter Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('read-daily-consumptions command started at ' . $startedAt);

        if ($credentials->is_authenticated == 1) {
            $this->dailyConsumptionService->getDailyDataFromAPI();
        } else {
            $this->info('# Kelin credentials not authenticated #');
        }
        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info("Took " . $totalTime . " seconds.");
        $this->info('#############################');
    }
}