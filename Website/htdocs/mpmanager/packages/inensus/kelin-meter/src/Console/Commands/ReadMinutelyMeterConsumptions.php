<?php


namespace Inensus\KelinMeter\Console\Commands;


use Carbon\Carbon;
use Illuminate\Console\Command;
use Inensus\KelinMeter\Services\KelinCredentialService;
use Inensus\KelinMeter\Services\MinutelyConsumptionService;


class ReadMinutelyMeterConsumptions extends Command
{
    protected $signature = 'kelin-meter:read-minutely-consumptions';
    protected $description = 'Reads daily meter consumptions.';

    private $minutelyConsumptionService;
    private $credentialService;

    public function __construct(
        MinutelyConsumptionService $minutelyConsumptionService,
        KelinCredentialService $credentialService
    ) {
        parent::__construct();
        $this->minutelyConsumptionService = $minutelyConsumptionService;
        $this->credentialService=$credentialService;
    }

    public function handle()
    {
        $credentials = $this->credentialService->getCredentials();
        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Kelin Meter Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('read-minutely-consumptions command started at ' . $startedAt);
        if ($credentials->is_authenticated==1){
            $this->minutelyConsumptionService->getMinutelyDataFromAPI();
        }else{
            $this->info('# Kelin credentials not authenticated #');
        }
        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info("Took " . $totalTime . " seconds.");
        $this->info('#############################');
    }
}