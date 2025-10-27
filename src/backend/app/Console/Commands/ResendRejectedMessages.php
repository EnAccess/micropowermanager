<?php

namespace App\Console\Commands;

use App\Exceptions\NoActiveSmsProviderException;
use App\Models\MpmPlugin;
use App\Models\Sms;
use App\Models\SmsAndroidSetting;
use App\Services\SmsGatewayResolverService;
use App\Sms\AndroidGateway;
use Illuminate\Support\Facades\Log;
use Inensus\AfricasTalking\AfricasTalkingGateway;
use Inensus\ViberMessaging\ViberGateway;

class ResendRejectedMessages extends AbstractSharedCommand {
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sms:resend-rejected {amount}';

    /**
     * The console command description.
     */
    protected $description = 'Takes unsent messages from the sms table and send them via the registered SMS-Provide';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(
        private Sms $sms,
        private SmsGatewayResolverService $gatewayResolver,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        if (!$this->gatewayResolver->hasActiveProvider()) {
            $this->error('No active SMS provider configured. Please enable an SMS provider plugin in the settings.');
            Log::error('Resend rejected messages command failed: No active SMS provider configured');

            return;
        }

        $amountToSend = (int) $this->argument('amount');
        $this->sms
            ->where('direction', 1)
            ->where('status', -1)
            ->orderBy('id')
            ->take($amountToSend)
            ->get()->each(function (Sms $sms) {
                try {
                    Log::info("Resending rejected message {$sms->id} to {$sms->receiver}");
                    [$gateway, $viberId] = $this->gatewayResolver->determineGateway($sms->receiver);

                    match ($gateway) {
                        SmsGatewayResolverService::VIBER_GATEWAY => $this->sendViaViber($sms, $viberId),
                        SmsGatewayResolverService::AFRICAS_TALKING_GATEWAY => $this->sendViaAfricasTalking($sms),
                        default => $this->sendViaAndroid($sms),
                    };
                } catch (NoActiveSmsProviderException $exception) {
                    Log::error("Failed to resend message {$sms->id}: {$exception->getMessage()}");
                    $this->error("Failed to resend message {$sms->id}: No active SMS provider");
                }
            });
    }

    private function sendViaViber(Sms $sms, ?string $viberId): void {
        if ($viberId) {
            resolve(ViberGateway::class)
                ->sendSms(
                    $sms->body,
                    $viberId,
                    $sms
                );
            $sms->status = 0;
            $sms->gateway_id = MpmPlugin::VIBER_MESSAGING;
            $sms->save();
        }
    }

    private function sendViaAfricasTalking(Sms $sms): void {
        resolve(AfricasTalkingGateway::class)
            ->sendSms(
                $sms->body,
                $sms->receiver,
                $sms
            );
        $sms->status = 0;
        $sms->gateway_id = MpmPlugin::AFRICAS_TALKING;
        $sms->save();
    }

    private function sendViaAndroid(Sms $sms): void {
        $smsAndroidSettings = SmsAndroidSetting::getResponsible();
        $callback = sprintf($smsAndroidSettings->callback, $sms->uuid);
        $sms->status = 0;
        $sms->gateway_id = $smsAndroidSettings->getId();
        $sms->save();

        resolve(AndroidGateway::class)
            ->sendSms(
                $sms->receiver,
                $sms->body,
                $callback,
                $smsAndroidSettings
            );
    }
}
