<?php

namespace Inensus\AfricasTalking;

use AfricasTalking\SDK\AfricasTalking;
use App\Models\Sms;
use Illuminate\Support\Facades\Log;
use Inensus\AfricasTalking\Exceptions\MessageNotSentException;
use Inensus\AfricasTalking\Services\AfricasTalkingCredentialService;
use Inensus\AfricasTalking\Services\AfricasTalkingMessageService;

class AfricasTalkingGateway {
    private $africasTalking;
    private $credentials;

    public function __construct(
        private AfricasTalkingCredentialService $credentialService,
        private AfricasTalkingMessageService $africasTalkingMessageService,
    ) {
        $credential = $this->credentialService->getCredentials();
        $apiKey = $credential->api_key;
        $username = $credential->username;
        $this->credentials = $credential;
        $this->africasTalking = new AfricasTalking($username, $apiKey);
    }

    public function sendSms(
        string $body,
        string $phoneNumber,
        Sms $registeredSms,
    ) {
        try {
            $sms = $this->africasTalking->sms();
            $phoneNumber = str_replace(' ', '', $phoneNumber);

            if (empty($phoneNumber)) {
                throw new MessageNotSentException('Invalid phone number');
            }

            $shortCode = $this->credentials->short_code;
            $response = $sms->send([
                'to' => $phoneNumber,
                'message' => $body,
                'from' => $shortCode,
            ]);

            $status = $response['status'];

            if ($status !== 'success') {
                throw new MessageNotSentException('AfricasTalking message sending failed with status: '.$status);
            }

            $data = (array) $response['data'];
            $messageData = (array) $data['SMSMessageData'];
            $recipients = $messageData['Recipients'];

            if (count($recipients) !== 1) {
                throw new MessageNotSentException('AfricasTalking message sending failed with wrong number of recipients');
            }

            $recipient = [];
            $africasTalkingMessage = [];

            foreach ($recipients as $recipient) {
                $recipient = (array) $recipient;

                if ($recipient['status'] !== 'Success') {
                    throw new MessageNotSentException('AfricasTalking message sending failed with recipient status: '.$recipient['status']);
                }

                $africasTalkingMessage = [
                    'status' => $recipient['status'],
                    'message_id' => $recipient['messageId'],
                    'status_code' => $recipient['statusCode'],
                    'sms_id' => $registeredSms->id,
                ];
            }

            $this->africasTalkingMessageService->create($africasTalkingMessage);
        } catch (\Exception $exception) {
            Log::error('AfricasTalking message sending failed', ['message' => $exception->getMessage()]);

            throw new MessageNotSentException('AfricasTalking message sending failed');
        }
    }
}
