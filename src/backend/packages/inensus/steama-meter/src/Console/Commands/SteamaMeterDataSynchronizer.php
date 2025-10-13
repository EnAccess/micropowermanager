<?php

namespace Inensus\SteamaMeter\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Models\Address\Address;
use App\Models\Cluster;
use App\Models\Person\Person;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use App\Traits\ScheduledPluginCommand;
use Carbon\Carbon;
use Inensus\SteamaMeter\Exceptions\CronJobException;
use Inensus\SteamaMeter\Services\SteamaAgentService;
use Inensus\SteamaMeter\Services\SteamaCustomerService;
use Inensus\SteamaMeter\Services\SteamaMeterService;
use Inensus\SteamaMeter\Services\SteamaSiteService;
use Inensus\SteamaMeter\Services\SteamaSyncSettingService;
use Inensus\SteamaMeter\Services\SteamaTransactionsService;
use Inensus\SteamaMeter\Services\StemaSyncActionService;

class SteamaMeterDataSynchronizer extends AbstractSharedCommand {
    use ScheduledPluginCommand;
    public const MPM_PLUGIN_ID = 2;

    protected $signature = 'steama-meter:dataSync';
    protected $description = 'Synchronize data that needs to be updated from Steamaco Meter.';

    public function __construct(
        private SteamaTransactionsService $steamaTransactionsService,
        private SteamaSyncSettingService $steamaSyncSettingservice,
        private SteamaMeterService $stemaMeterService,
        private SteamaCustomerService $steamaCustomerService,
        private SteamaSiteService $steamaSiteService,
        private SteamaAgentService $steamaAgentService,
        private StemaSyncActionService $steamaSyncActionService,
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
        $this->info('# Steamaco Meter Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('dataSync command started at '.$startedAt);

        $syncActions = $this->steamaSyncActionService->getActionsNeedsToSync();
        try {
            $this->steamaSyncSettingservice->getSyncSettings()->each(function ($syncSetting) use ($syncActions): true {
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
                        'message' => '~ Steamaco-Meter Package ~ '.$syncSetting->action_name.
                            ' synchronization has failed by unrealizable reason that occurred
                             on Steamaco Meter API. '.$syncSetting->action_name.' synchronization is going to be retried at '.
                            $nextSync,
                        'phone' => $adminAddress->phone,
                    ];

                    $smsService = app()->make(SmsService::class);
                    $smsService->sendSms($data, SmsTypes::MANUAL_SMS, SmsConfigs::class);
                } else {
                    switch ($syncSetting->action_name) {
                        case 'Sites':
                            $this->steamaSiteService->sync();
                            break;
                        case 'Customers':
                            $this->steamaCustomerService->sync();
                            break;
                        case 'Meters':
                            $this->stemaMeterService->sync();
                            break;
                        case 'Agents':
                            $this->steamaAgentService->sync();
                            break;
                        case 'Transactions':
                            $this->steamaTransactionsService->sync();
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
