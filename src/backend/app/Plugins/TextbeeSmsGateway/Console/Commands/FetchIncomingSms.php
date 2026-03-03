<?php

declare(strict_types=1);

namespace App\Plugins\TextbeeSmsGateway\Console\Commands;

use App\Console\Commands\AbstractSharedCommand;
use App\Models\MpmPlugin;
use App\Plugins\SmsTransactionParser\Models\SmsTransaction;
use App\Plugins\SmsTransactionParser\Services\SmsTransactionService;
use App\Plugins\TextbeeSmsGateway\Services\TextbeeSmsPollingService;
use App\Traits\ScheduledPluginCommand;
use Carbon\Carbon;

class FetchIncomingSms extends AbstractSharedCommand {
    use ScheduledPluginCommand;

    public const MPM_PLUGIN_ID = MpmPlugin::TEXTBEE_SMS_GATEWAY;

    protected $signature = 'textbee-sms-gateway:fetch-incoming-sms';
    protected $description = 'Poll TextBee gateway for incoming SMS and process them as transactions';

    public function __construct(
        private TextbeeSmsPollingService $pollingService,
        private SmsTransactionService $smsTransactionService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        if (!$this->checkForPluginStatusIsActive(self::MPM_PLUGIN_ID)) {
            return;
        }

        $timeStart = microtime(true);
        $this->info('#############################');
        $this->info('# TextBee SMS Polling #');
        $startedAt = Carbon::now()->toIso8601ZuluString();
        $this->info('fetch-incoming-sms command started at '.$startedAt);

        $messages = $this->pollingService->fetchNewMessages();
        $processed = 0;
        $skipped = 0;

        foreach ($messages as $message) {
            $result = $this->smsTransactionService->processIncomingSms(
                $message['body'],
                $message['sender'],
            );

            if ($result instanceof SmsTransaction) {
                ++$processed;
            } else {
                ++$skipped;
            }
        }

        $timeEnd = microtime(true);
        $totalTime = $timeEnd - $timeStart;
        $this->info("Processed: {$processed}, Skipped: {$skipped}");
        $this->info('Took '.$totalTime.' seconds.');
        $this->info('#############################');
    }
}
