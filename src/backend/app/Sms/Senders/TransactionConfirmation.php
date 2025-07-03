<?php

namespace App\Sms\Senders;

use App\Exceptions\MissingSmsReferencesException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

class TransactionConfirmation extends SmsSender {
    protected mixed $data;
    public string $body = '';

    /** @var array<string, string> */
    protected ?array $references = [
        'header' => 'SmsTransactionHeader',
        'footer' => 'SmsTransactionFooter',
        'body' => '',
    ];
    public const ENERGY_CONFIRMATION = 'energy';
    public const ACCESS_RATE_PAYMENT = 'access rate';
    public const ASSET_RATE_PAYMENT = 'loan rate';

    public function prepareBody(): void {
        $this->data->paymentHistories()->each(function ($payment) {
            switch ($payment->payment_type) {
                case self::ENERGY_CONFIRMATION:
                    $this->prepareBodyByClassReference('EnergyConfirmation', $payment);
                    break;
                case self::ACCESS_RATE_PAYMENT:
                    $this->prepareBodyByClassReference('AccessRateConfirmation', $payment);
                    break;
                case self::ASSET_RATE_PAYMENT:
                    $this->prepareBodyByClassReference('AssetRatePayment', $payment);
                    break;
            }
        });
        $this->preparePricingDetails();
    }

    public function preparePricingDetails(): void {
        $this->prepareBodyByClassReference('PricingDetails', $this->data);
    }

    private function prepareBodyByClassReference(string $reference, mixed $payload): void {
        try {
            $smsBody = $this->smsBodyService->getSmsBodyByReference($reference);
        } catch (ModelNotFoundException $exception) {
            $exception = new MissingSmsReferencesException($reference.' SMS body record not found in database');
            Log::error('SMS Body preparing failed.', ['message : ' => $exception->getMessage()]);

            return;
        }
        $className = $this->parserSubPath.$reference;
        $smsObject = new $className($payload);
        try {
            $this->body .= $smsObject->parseSms($smsBody->body);
        } catch (\Exception $exception) {
            Log::error('SMS Body parsing failed.', ['message : ' => $exception->getMessage()]);

            return;
        }
    }
}
