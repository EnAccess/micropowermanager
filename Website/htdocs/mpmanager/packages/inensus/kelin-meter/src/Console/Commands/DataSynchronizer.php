<?php

namespace Inensus\KelinMeter\Console\Commands;

use App\Jobs\SmsProcessor;
use App\Models\Address\Address;
use App\Models\Cluster;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Inensus\KelinMeter\Exceptions\CronJobException;
use Inensus\KelinMeter\Services\KelinCustomerService;
use Inensus\KelinMeter\Services\KelinMeterService;
use Inensus\KelinMeter\Services\KelinSyncActionService;
use Inensus\KelinMeter\Services\KelinSyncSettingService;


class DataSynchronizer extends Command
{
    protected $signature = 'kelin-meter:dataSync';
    protected $description = 'Synchronize data that needs to be updated from Kelin platform.';


    private $address;
    private $cluster;
    private $syncSettingService;
    private $meterService;
    private $customerService;
    private $syncActionService;

    public function __construct(

        KelinSyncSettingService $syncSettingService,
        KelinMeterService $meterService,
        KelinCustomerService $customerService,
        KelinSyncActionService $syncActionService,
        Address $address,
        Cluster $cluster
    ) {
        parent::__construct();
        $this->syncSettingService=$syncSettingService;
        $this->meterService =$meterService;
        $this->customerService =$customerService;
        $this->syncActionService = $syncActionService;
        $this->address = $address;
        $this->cluster=$cluster;
    }

    public function handle(): void
    {
        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Kelin Meter Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('dataSync command started at ' . $startedAt);

        $syncActions = $this->syncActionService->getActionsNeedsToSync();
        try {
            $this->syncSettingService->getSyncSettings()->each(function ($syncSetting) use ($syncActions) {
                $syncAction = $syncActions->where('sync_setting_id', $syncSetting->id)->first();
                if (!$syncAction) {
                    return true;
                }
                if ($syncAction->attempts >= $syncSetting->max_attempts) {
                    $nextSync = Carbon::parse($syncAction->next_sync)->addHours(2);
                    $syncAction->next_sync = $nextSync;
                    $cluster = $this->cluster->newQuery()->with('manager')->first();
                    if(!$cluster){
                        return true;
                    }
                    $adminAddress = $this->address->whereHasMorph('owner', [$cluster->manager])->first();
                    if (!$adminAddress) {
                        return true;
                    }
                    $data = [
                        'message' =>'~ Kelin-Meter Package ~ ' .$syncSetting->action_name .
                            ' synchronization has failed by unrealizable reason that occurred
                             on Steamaco Meter API. ' .$syncSetting->action_name .' synchronization is going to be retried at ' .
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
                        case 'Customers':
                            $this->customerService->sync();
                            break;
                        case 'Meters':
                            $this->meterService->sync();
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
