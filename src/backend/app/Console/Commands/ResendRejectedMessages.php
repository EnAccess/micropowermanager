<?php

namespace App\Console\Commands;

use App\Models\Sms;
use App\Services\SmsGatewayResolverService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Log;

class ResendRejectedMessages extends AbstractSharedCommand {
    public const MAX_ATTEMPTS = 3;

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
     */
    public function __construct(
        private Sms $sms,
        private SmsGatewayResolverService $gatewayResolver,
        private SmsService $smsService,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        if (!$this->gatewayResolver->hasActiveProvider()) {
            return;
        }

        $amountToSend = (int) $this->argument('amount');
        $this->sms
            ->where('direction', Sms::DIRECTION_OUTGOING)
            ->where('status', Sms::STATUS_FAILED)
            ->where('attempts', '<=', self::MAX_ATTEMPTS)
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

                    $this->smsService->markSent($sms, $resolved['gatewayId']);
                } catch (\Throwable $exception) {
                    $this->smsService->markFailed($sms, $exception);
                    $message = "Failed to resend message {$sms->id}: {$exception->getMessage()}";

                    if ($sms->attempts >= self::MAX_ATTEMPTS) {
                        Log::error($message);
                    } else {
                        Log::warning($message);
                    }

                    $this->error($message);
                }
            });
    }
}
