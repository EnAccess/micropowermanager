<?php

namespace App\Console\Commands;

use App\Exceptions\NoActiveSmsProviderException;
use App\Models\Sms;
use App\Services\SmsGatewayResolverService;
use Illuminate\Support\Facades\Log;

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

                    $resolved = $this->gatewayResolver->resolveGatewayAndArgs($gateway, $sms, [
                        'viberId' => $viberId,
                    ]);

                    $resolved['gateway']->sendSms(...$resolved['args']);

                    $sms->status = 0;
                    $sms->gateway_id = $resolved['gatewayId'];
                    $sms->save();
                } catch (NoActiveSmsProviderException $exception) {
                    Log::error("Failed to resend message {$sms->id}: {$exception->getMessage()}");
                    $this->error("Failed to resend message {$sms->id}: No active SMS provider");
                }
            });
    }
}
