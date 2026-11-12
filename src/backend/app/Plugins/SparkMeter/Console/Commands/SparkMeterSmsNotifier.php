<?php

namespace App\Plugins\SparkMeter\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Models\MpmPlugin;
use App\Plugins\SparkMeter\Jobs\SendSparkMeterSmsNotifications;
use App\Plugins\SparkMeter\Services\CredentialService;
use App\Traits\ScheduledPluginCommand;
use Carbon\Carbon;

class SparkMeterSmsNotifier extends AbstractSharedCommand {
    use ScheduledPluginCommand;

    protected $signature = 'spark-meter:smsNotifier';
    protected $description = 'Notifies customers on payments and low balance limits for SparkMeters';

    public function __construct(private CredentialService $credentialService) {
        parent::__construct();
    }

    public function handle(): void {
        if (!$this->checkForPluginStatusIsActive(MpmPlugin::SPARK_METER)) {
            return;
        }

        if (!$this->credentialService->isAuthenticated()) {
            $this->info('# Spark Meter credentials not authenticated #');

            return;
        }

        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Spark Meter Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('smsNotifier command started at '.$startedAt);

        dispatch(new SendSparkMeterSmsNotifications());

        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info('Took '.$totalTime.' seconds.');
        $this->info('#############################');
    }
}
