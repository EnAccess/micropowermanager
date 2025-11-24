<?php

namespace Inensus\TextbeeSmsGateway;

use App\Models\Sms;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Inensus\TextbeeSmsGateway\Exceptions\MessageNotSentException;
use Inensus\TextbeeSmsGateway\Models\TextbeeCredential;
use Inensus\TextbeeSmsGateway\Services\TextbeeCredentialService;
use Inensus\TextbeeSmsGateway\Services\TextbeeMessageService;

class TextbeeSmsGateway {
    private const BASE_URL = 'https://api.textbee.dev/api/v1';
    private TextbeeCredential $credentials;

    public function __construct(
        private TextbeeCredentialService $credentialService,
        private TextbeeMessageService $textbeeMessageService,
    ) {
        $this->credentials = $this->credentialService->getCredentials();
    }

    public function sendSms(
        string $body,
        string $phoneNumber,
        Sms $registeredSms,
    ): void {
        try {
            $phoneNumber = str_replace(' ', '', $phoneNumber);

            if (empty($phoneNumber)) {
                throw new MessageNotSentException('Invalid phone number');
            }

            if (empty($this->credentials->api_key) || empty($this->credentials->device_id)) {
                throw new MessageNotSentException('TextBee credentials not configured');
            }

            $url = self::BASE_URL.'/gateway/devices/'.$this->credentials->device_id.'/send-sms';

            $response = Http::withHeaders([
                'x-api-key' => $this->credentials->api_key,
                'Accept' => 'application/json',
            ])->post($url, [
                'recipients' => [$phoneNumber],
                'message' => $body,
            ]);

            if (!$response->successful()) {
                $errorMessage = $response->json('message') ?? 'Unknown error';
                Log::error('TextBee API error', [
                    'status' => $response->status(),
                    'message' => $errorMessage,
                    'body' => $response->body(),
                ]);

                throw new MessageNotSentException('TextBee message sending failed: '.$errorMessage);
            }

            $data = $response->json('data');

            if (!$data) {
                throw new MessageNotSentException('TextBee response missing data field');
            }

            $messageId = $data['id'] ?? null;
            $status = $data['status'] ?? null;
            $createdAt = $data['createdAt'] ?? null;

            if (!$messageId) {
                throw new MessageNotSentException('TextBee response missing message ID');
            }

            $textbeeMessage = [
                'status' => $status ?? 'PENDING',
                'message_id' => $messageId,
                'sms_id' => $registeredSms->id,
                'created_at_textbee' => $createdAt,
            ];

            $this->textbeeMessageService->create($textbeeMessage);
        } catch (MessageNotSentException $exception) {
            throw $exception;
        } catch (\Exception $exception) {
            Log::error('TextBee message sending failed', [
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            throw new MessageNotSentException('TextBee message sending failed: '.$exception->getMessage());
        }
    }
}
