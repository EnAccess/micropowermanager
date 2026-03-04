<?php

namespace App\Listeners;

use App\Events\TransactionSuccessfulEvent;
use App\Models\MainSettings;
use App\Models\Transaction\Transaction;
use App\Services\SmsService;
use App\Sms\Senders\SmsConfigs;
use App\Sms\SmsTypes;
use Illuminate\Support\Facades\Log;

/**
 * Sends unified transaction confirmation SMS for all channels (cash, Paystack, etc.).
 * Token-focused: meter → token + kWh; SHS/PayGo → token + duration; no token → amount only.
 */
class SendTransactionConfirmationSmsListener {
    public function __construct(private SmsService $smsService) {}

    public function handle(TransactionSuccessfulEvent $event): void {
        $this->onTransactionSuccess($event->transaction);
    }

    private function onTransactionSuccess(Transaction $transaction): void {
        if ($transaction->sms()->exists()) {
            return;
        }

        $mainSettings = MainSettings::query()->first();
        if ($mainSettings && !$mainSettings->transaction_sms_enabled) {
            return;
        }

        try {
            if ($transaction->device()->exists()) {
                $transaction->load([
                    'token',
                    'device.person.addresses',
                ]);
            } else {
                $transaction->load([
                    'token',
                    'nonPaygoAppliance.person.addresses',
                ]);
            }
            // For now we only limit sending sms confirmation for token enabled transaction
            // we potentionally will include other usecases in the future.
            if ($transaction->token) {
                $this->smsService->sendSms($transaction, SmsTypes::TRANSACTION_CONFIRMATION, SmsConfigs::class);
            }
        } catch (\RuntimeException $e) {
            Log::error('SendTransactionConfirmationSms failed', [
                'transaction_id' => $transaction->id,
                'message' => $e->getMessage(),
            ]);
            if (str_contains($e->getMessage(), 'No phone available')) {
                return;
            }
            throw $e;
        } catch (\Throwable $e) {
            Log::error('SendTransactionConfirmationSms failed', [
                'transaction_id' => $transaction->id,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
