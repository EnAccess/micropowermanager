<?php

namespace Inensus\SparkMeter\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Models\Address\Address;
use App\Models\Cluster;
use App\Models\Person\Person;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use App\Traits\ScheduledPluginCommand;
use Carbon\Carbon;
use Inensus\SparkMeter\Exceptions\CronJobException;
use Inensus\SparkMeter\Services\CustomerService;
use Inensus\SparkMeter\Services\MeterModelService;
use Inensus\SparkMeter\Services\SiteService;
use Inensus\SparkMeter\Services\SmSyncActionService;
use Inensus\SparkMeter\Services\SmSyncSettingService;
use Inensus\SparkMeter\Services\TariffService;
use Inensus\SparkMeter\Services\TransactionService;

class SparkMeterDataSynchronizer extends AbstractSharedCommand {
    use ScheduledPluginCommand;
    public const MPM_PLUGIN_ID = 2;

    protected $signature = 'spark-meter:dataSync';
    protected $description = 'Synchronize data that needs to be updated from Spark Meter.';

    public function __construct(
        private SiteService $smSiteService,
        private MeterModelService $smMeterModelService,
        private TariffService $smTariffService,
        private SmSyncSettingService $smSyncSettingService,
        private TransactionService $smTransactionService,
        private CustomerService $smCustomerService,
        private SmSyncActionService $smSyncActionService,
        private Address $address,
        private Cluster $cluster,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        if (!$this->checkForPluginStatusIsActive(self::MPM_PLUGIN_ID)) {
            return;
        }

        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Spark Meter Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('dataSync command started at '.$startedAt);

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
                    $adminAddress = $this->address->whereHasMorph(
                        'owner',
                        [Person::class],
                        function ($q) use ($adminId) {
                            $q->where('id', $adminId);
                        }
                    )->first();

                    if (!$adminAddress) {
                        return true;
                    }
                    $data = [
                        'message' => '~ Spark-Meter Package ~ '.$syncSetting->action_name.
                            ' synchronization has failed by unrealizable reason that occurred
                             on Spark Meter API. '
                            .$syncSetting->action_name.' synchronization is going to be retried at '.
                            $nextSync,
                        'phone' => $adminAddress->phone,
                    ];

                    $smsService = app()->make(SmsService::class);
                    $smsService->sendSms($data, SmsTypes::MANUAL_SMS, SmsConfigs::class);
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
            $this->warn('dataSync command is failed. message => '.$e->getMessage());
        }
        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info('Took '.$totalTime.' seconds.');
        $this->info('#############################');
    }
}
