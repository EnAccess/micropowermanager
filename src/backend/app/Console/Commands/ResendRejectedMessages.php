<?php

namespace App\Console\Commands;

use App\Models\MpmPlugin;
use App\Models\Sms;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappApiException;
use App\Plugins\WhatsappMessaging\Exceptions\WhatsappTemplateNotConfiguredException;
use App\Plugins\WhatsappMessaging\WhatsappMessagingGateway;
use App\Services\SmsGatewayResolverService;
use App\Services\SmsService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Log;

class ResendRejectedMessages extends AbstractSharedCommand {
    /** Counts every hand-off to an SMS gateway, the original send included, so two means one resend. */
    public const MAX_ATTEMPTS = 2;

    /**
     * The name and signature of the console command.
     */
    protected $signature = 'sms:resend-rejected {amount}';

    /**
     * The console command description.
     */
    protected $description = 'Retries the messages waiting for a retry: by the registered SMS provider, or by WhatsApp where that is the only channel';

    /**
     * Create a new command instance.
     */
    public function __construct(
        private Sms $sms,
        private SmsGatewayResolverService $gatewayResolver,
        private SmsService $smsService,
        private WhatsappMessagingGateway $whatsappGateway,
    ) {
        parent::__construct();
    }

    public function handle(): void {
        if (!$this->gatewayResolver->isSmsGatewayConfigured() && !$this->whatsappGateway->isAvailable()) {
            return;
        }

        $amountToSend = (int) $this->argument('amount');
        $this->sms
            ->where('direction', Sms::DIRECTION_OUTGOING)
            ->where(fn (Builder $query) => $query
                ->where('status', Sms::STATUS_WAITING_FOR_RETRY)
                // SMS gateway delivery reports (Africa's Talking) mark a row failed and leave the resend to this command.
                ->orWhere(fn (Builder $reported) => $reported
                    ->where('status', Sms::STATUS_FAILED)
                    ->where('attempts', '<', self::MAX_ATTEMPTS)
                    ->where(fn (Builder $gateway) => $gateway->whereNull('gateway_id')->orWhere('gateway_id', '!=', MpmPlugin::WHATSAPP_MESSAGING))))
            ->orderBy('id')
            ->take($amountToSend)
            ->get()->each(function (Sms $sms) {
                if ((int) $sms->gateway_id === MpmPlugin::WHATSAPP_MESSAGING) {
                    $this->retryOnWhatsapp($sms);
                } else {
                    $this->retryOnSmsGateway($sms);
                }
            });
    }

    // Reached only without an SMS gateway: with one, a WhatsApp failure goes straight to SMS and is never retried here.
    private function retryOnWhatsapp(Sms $sms): void {
        Log::info("Resending message {$sms->id} to {$sms->receiver} by WhatsApp");

        try {
            if ($this->whatsappGateway->resend($sms)) {
                $this->smsService->markSent($sms, MpmPlugin::WHATSAPP_MESSAGING);

                return;
            }

            $this->smsService->whatsappFailed($sms, 'The customer can no longer be reached on WhatsApp (opted out or the plugin was turned off).', false);
        } catch (WhatsappApiException|WhatsappTemplateNotConfiguredException $exception) {
            $this->smsService->whatsappFailed($sms, $exception->getMessage(), $exception instanceof WhatsappApiException && $exception->isTemporary());
        }

        $this->reportFailure($sms);
    }

    private function retryOnSmsGateway(Sms $sms): void {
        Log::info("Resending rejected message {$sms->id} to {$sms->receiver}");

        try {
            if ($this->smsService->sendViaSmsGateway($sms)) {
                return;
            }
        } catch (\Throwable $exception) {
            $this->smsService->markFailed($sms, $exception);
        }

        $this->reportFailure($sms);
    }

    private function reportFailure(Sms $sms): void {
        $message = "Failed to resend message {$sms->id}: {$sms->error_message}";

        if ((int) $sms->status === Sms::STATUS_FAILED) {
            Log::error($message);
        } else {
            Log::warning($message);
        }

        $this->error($message);
    }
}
