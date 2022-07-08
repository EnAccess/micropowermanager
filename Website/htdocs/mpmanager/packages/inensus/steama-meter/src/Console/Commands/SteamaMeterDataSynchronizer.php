<?php

namespace Inensus\SteamaMeter\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Jobs\SmsProcessor;
use App\Models\Address\Address;
use App\Models\User;
use App\Models\Cluster;
use App\Models\Person\Person;
use App\Sms\SmsTypes;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Inensus\SteamaMeter\Services\SteamaAgentService;
use Inensus\SteamaMeter\Services\SteamaCustomerService;
use Inensus\SteamaMeter\Services\SteamaMeterService;
use Inensus\SteamaMeter\Services\SteamaSiteService;
use Inensus\SteamaMeter\Services\SteamaSyncSettingService;
use Inensus\SteamaMeter\Services\SteamaTransactionsService;
use Inensus\SteamaMeter\Services\StemaSyncActionService;
use Inensus\StemaMeter\Exceptions\CronJobException;


class SteamaMeterDataSynchronizer extends AbstractSharedCommand
{
    protected $signature = 'steama-meter:dataSync';
    protected $description = 'Synchronize data that needs to be updated from Steamaco Meter.';

    private $steamaTransactionsService;
    private $steamaSyncSettingservice;
    private $stemaMeterService;
    private $steamaCustomerService;
    private $steamaSiteService;
    private $steamaAgentService;
    private $steamaSyncActionService;
    private $address;
    private $cluster;

    public function __construct(
        SteamaTransactionsService $steamaTransactionsService,
        SteamaSyncSettingService $steamaSyncSettingService,
        SteamaMeterService $steamaMeterService,
        SteamaCustomerService $steamaCustomerService,
        SteamaSiteService $steamaSiteService,
        SteamaAgentService $steamaAgentService,
        StemaSyncActionService $steamaSyncActionService,
        Address $address,
        Cluster $cluster
    ) {
        parent::__construct();
        $this->steamaTransactionsService = $steamaTransactionsService;
        $this->steamaSyncSettingservice = $steamaSyncSettingService;
        $this->stemaMeterService = $steamaMeterService;
        $this->steamaCustomerService = $steamaCustomerService;
        $this->steamaSiteService = $steamaSiteService;
        $this->steamaAgentService = $steamaAgentService;
        $this->steamaSyncActionService = $steamaSyncActionService;
        $this->address = $address;
        $this->cluster=$cluster;
    }


  public  function runInCompanyScope(): void
    {
        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# Steamaco Meter Package #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('dataSync command started at ' . $startedAt);

        $syncActions = $this->steamaSyncActionService->getActionsNeedsToSync();
        try {
            $this->steamaSyncSettingservice->getSyncSettings()->each(function ($syncSetting) use ($syncActions) {
                $syncAction = $syncActions->where('sync_setting_id', $syncSetting->id)->first();
                if (!$syncAction) {
                    return true;
                }
                if ($syncAction->attempts >= $syncSetting->max_attempts) {
                    $nextSync = Carbon::now()->addHours(2);
                    $syncAction->next_sync = $nextSync;
                    $syncAction->save();
                    $cluster = $this->cluster->newQuery()->with('manager')->first();
                    if(!$cluster){
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
                        'message' =>'~ Steamaco-Meter Package ~ ' .$syncSetting->action_name .
                            ' synchronization has failed by unrealizable reason that occurred
                             on Steamaco Meter API. ' .$syncSetting->action_name .' synchronization is going to be retried at ' .
                            $nextSync,
                        'phone' => $adminAddress->phone
                    ];
                    SmsProcessor::dispatch(
                        $data,
                        SmsTypes::MANUAL_SMS
                    )->allOnConnection('redis')->onQueue(\config('services.queues.sms'));
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
            $this->warn('dataSync command is failed. message => ' . $e->getMessage());
        }
        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info("Took " . $totalTime . " seconds.");
        $this->info('#############################');
    }
}
