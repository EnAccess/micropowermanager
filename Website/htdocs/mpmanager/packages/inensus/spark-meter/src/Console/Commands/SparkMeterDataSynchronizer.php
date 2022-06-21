<?php

namespace Inensus\SparkMeter\Console\Commands;

use App\Jobs\SmsProcessor;
use App\Models\Address\Address;
use App\Models\User;
use App\Models\Person\Person;
use App\Models\Cluster;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Inensus\SparkMeter\Exceptions\CronJobException;
use Inensus\SparkMeter\Services\CustomerService;
use Inensus\SparkMeter\Services\MeterModelService;
use Inensus\SparkMeter\Services\SiteService;
use Inensus\SparkMeter\Services\SmSyncActionService;
use Inensus\SparkMeter\Services\SmSyncSettingService;
use Inensus\SparkMeter\Services\TariffService;
use Inensus\SparkMeter\Services\TransactionService;

class SparkMeterDataSynchronizer extends Command
{
    protected $signature = 'spark-meter:dataSync';
    protected $description = 'Synchronize data that needs to be updated from Spark Meter.';

    private $smSiteService;
    private $smMeterModelService;
    private $smTariffService;
    private $smCustomerService;
    private $smSyncSettingService;
    private $smSyncActionService;
    private $smTransactionService;
    private $address;
    private $cluster;

    public function __construct(
        SiteService $smSiteService,
        MeterModelService $smMeterModelService,
        TariffService $smTariffService,
        SmSyncSettingService $smSyncSettingService,
        TransactionService $smTransactionService,
        CustomerService $smCustomerService,
        SmSyncActionService $smSyncActionService,
        Address $address,
        Cluster $cluster
    ) {
        parent::__construct();
        $this->smSiteService = $smSiteService;
        $this->smMeterModelService = $smMeterModelService;
        $this->smTariffService = $smTariffService;
        $this->smTransactionService = $smTransactionService;
        $this->smCustomerService = $smCustomerService;
        $this->smSyncActionService = $smSyncActionService;
        $this->smSyncSettingService = $smSyncSettingService;
        $this->address = $address;
        $this->cluster = $cluster;
    }

    public function handle(): void
    {
        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Spark Meter Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('dataSync command started at ' . $startedAt);

        $syncActions = $this->smSyncActionService->getActionsNeedsToSync();
        try {
            $this->smSyncSettingService->getSyncSettings()->each(function ($syncSetting) use ($syncActions) {
                $syncAction = $syncActions->where('sync_setting_id', $syncSetting->id)->first();
                if (!$syncAction) {
                    return true;
                }
                if ($syncAction->attempts >= $syncSetting->max_attempts) {
                    $nextSync = Carbon::now()->addHours(2);
                    $syncAction->next_sync = $nextSync;
                    $syncAction->save();
                    $cluster = $this->cluster->newQuery()->with('manager')->first();
                    if (!$cluster) {
                        return true;
                    }
                    $adminId = $cluster->manager->id;
                    $adminAddress = $this->address->whereHasMorph('owner', [Person::class],
                        function ($q) use ($adminId) {
                            $q->where('id', $adminId);
                        })->first();

                    if (!$adminAddress) {
                        return true;
                    }
                    $data = [
                        'message' => '~ Spark-Meter Package ~ ' . $syncSetting->action_name .
                            ' synchronization has failed by unrealizable reason that occurred
                             on Spark Meter API. '
                            . $syncSetting->action_name . ' synchronization is going to be retried at ' .
                            $nextSync,
                        'phone' => $adminAddress->phone
                    ];
                    SmsProcessor::dispatch(
                        $data,
                        SmsTypes::MANUAL_SMS,
                        SmsConfigs::class
                    )->allOnConnection('redis')->onQueue(\config('services.queues.sms'));
                } else {
                    switch ($syncSetting->action_name) {
                        case 'Sites':
                            $this->smSiteService->sync();
                            break;
                        case 'MeterModels':
                            $this->smMeterModelService->sync();
                            break;
                        case 'Tariffs':
                            $this->smTariffService->sync();
                            break;
                        case 'Customers':
                            $this->smCustomerService->sync();
                            break;
                        case 'Transactions':
                            $this->smTransactionService->sync();
                            break;
                    }
                }
                return true;
            });
        } catch (CronJobException $e) {
            $this->warn('dataSync command is failed. message => ' . $e->getMessage());
        }
        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info("Took " . $totalTime . " seconds.");
        $this->info('#############################');
    }
}
