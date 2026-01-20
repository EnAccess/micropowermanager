<?php

namespace App\Plugins\AfricasTalking;

use AfricasTalking\SDK\AfricasTalking;
use App\Models\Sms;
use App\Plugins\AfricasTalking\Exceptions\MessageNotSentException;
use App\Plugins\AfricasTalking\Models\AfricasTalkingCredential;
use App\Plugins\AfricasTalking\Services\AfricasTalkingCredentialService;
use App\Plugins\AfricasTalking\Services\AfricasTalkingMessageService;
use Illuminate\Support\Facades\Log;

class AfricasTalkingGateway {
    private AfricasTalking $africasTalking;
    private AfricasTalkingCredential $credentials;

    public function __construct(
        private AfricasTalkingCredentialService $credentialService,
        private AfricasTalkingMessageService $africasTalkingMessageService,
    ) {
        $this->credentials = $this->credentialService->getCredentials();
        $this->africasTalking = new AfricasTalking(
            $this->credentials->username,
            $this->credentials->api_key
        );
    }

    public function sendSms(
        string $body,
        string $phoneNumber,
        Sms $registeredSms,
    ): void {
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
