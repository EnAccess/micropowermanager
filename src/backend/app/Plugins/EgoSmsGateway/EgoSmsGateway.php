<?php

namespace App\Plugins\EgoSmsGateway;

use App\Models\Sms;
use App\Plugins\EgoSmsGateway\Exceptions\MessageNotSentException;
use App\Plugins\EgoSmsGateway\Models\EgoSmsCredential;
use App\Plugins\EgoSmsGateway\Models\EgoSmsMessage;
use App\Plugins\EgoSmsGateway\Services\EgoSmsCredentialService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class EgoSmsGateway {
    private const string API_URL = 'https://www.egosms.co/api/v1/json/';
    private const int TIMEOUT_SECONDS = 30;

    public function __construct(
        private EgoSmsCredentialService $credentialService,
        private EgoSmsMessage $egoSmsMessage,
    ) {}

    public function sendSms(string $body, string $phoneNumber, Sms $registeredSms): void {
        $credentials = $this->credentialService->getCredentials();

        if (!$credentials instanceof EgoSmsCredential || empty($credentials->username) || empty($credentials->password) || empty($credentials->sender_id)) {
            throw new MessageNotSentException('EgoSMS credentials are not configured.');
        }

        // EgoSMS expects the international number without the leading plus sign.
        $number = ltrim(str_replace(' ', '', $phoneNumber), '+');

        if ($number === '') {
            throw new MessageNotSentException('Invalid phone number');
        }

        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)->asJson()->post(self::API_URL, [
                'method' => 'SendSms',
                'userdata' => [
                    'username' => $credentials->username,
                    'password' => $credentials->password,
                ],
                'msgdata' => [
                    [
                        'number' => $number,
                        'message' => $body,
                        'senderid' => $credentials->sender_id,
                        'priority' => '0',
                    ],
                ],
            ]);
        } catch (ConnectionException $exception) {
            throw new MessageNotSentException('Could not reach EgoSMS: '.$exception->getMessage(), $exception->getCode(), $exception);
        }

        if ($response->json('Status') !== 'OK') {
            $reason = $response->json('Message') ?? "unexpected response (HTTP {$response->status()})";

            throw new MessageNotSentException('EgoSMS message sending failed: '.$reason);
        }

        $this->egoSmsMessage->newQuery()->create([
            'sms_id' => $registeredSms->id,
            'follow_up_code' => $response->json('MsgFollowUpUniqueCode'),
        ]);
    }
}
