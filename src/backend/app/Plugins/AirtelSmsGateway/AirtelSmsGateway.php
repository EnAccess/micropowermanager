<?php

namespace App\Plugins\AirtelSmsGateway;

use App\Models\Sms;
use App\Plugins\AirtelSmsGateway\Exceptions\MessageNotSentException;
use App\Plugins\AirtelSmsGateway\Models\AirtelSmsCredential;
use App\Plugins\AirtelSmsGateway\Models\AirtelSmsMessage;
use App\Plugins\AirtelSmsGateway\Services\AirtelSmsCredentialService;
use App\Plugins\AirtelSmsGateway\Smpp\SmppTransmitter;

class AirtelSmsGateway {
    public function __construct(
        private AirtelSmsCredentialService $credentialService,
        private AirtelSmsMessage $airtelSmsMessage,
        private SmppTransmitter $smppTransmitter,
    ) {}

    public function sendSms(string $body, string $phoneNumber, Sms $registeredSms): void {
        $credentials = $this->credentialService->getCredentials();

        if (!$credentials instanceof AirtelSmsCredential
            || empty($credentials->host) || empty($credentials->port) || empty($credentials->system_id)
            || empty($credentials->password) || empty($credentials->source_address)) {
            throw new MessageNotSentException('Airtel SMS credentials are not configured.');
        }

        // SMPP takes the international number as digits, without the leading plus sign.
        $destinationNumber = (string) preg_replace('/\D/', '', $phoneNumber);

        if ($destinationNumber === '') {
            throw new MessageNotSentException('Invalid phone number');
        }

        $messageId = $this->smppTransmitter->send(
            $credentials->host,
            $credentials->port,
            $credentials->system_id,
            $credentials->password,
            $credentials->source_address,
            $destinationNumber,
            $body,
        );

        $this->airtelSmsMessage->newQuery()->create([
            'sms_id' => $registeredSms->id,
            'message_id' => $messageId,
        ]);
    }
}
