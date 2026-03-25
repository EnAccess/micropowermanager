<?php

declare(strict_types=1);

namespace App\Plugins\TextbeeSmsGateway\Services;

use App\Plugins\TextbeeSmsGateway\Models\TextbeeCredential;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextbeeSmsPollingService {
    private const BASE_URL = 'https://api.textbee.dev/api/v1';
    private const CACHE_KEY = 'textbee_last_polled_at';

    public function __construct(
        private TextbeeCredentialService $credentialService,
    ) {}

    /**
     * @return array<int, array{sender: string, body: string, receivedAt: string}>
     */
    public function fetchNewMessages(): array {
        $credentials = $this->credentialService->getCredentials();

        if (!$credentials instanceof TextbeeCredential || empty($credentials->api_key) || empty($credentials->device_id)) {
            Log::warning('TextBee credentials not configured for SMS polling');

            return [];
        }

        $url = self::BASE_URL.'/gateway/devices/'.$credentials->device_id.'/get-received-sms';

        $lastPolledAt = Cache::get(self::CACHE_KEY);

        $queryParams = ['page' => 1, 'limit' => 20];
        if ($lastPolledAt) {
            $queryParams['receivedAfter'] = $lastPolledAt;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $credentials->api_key,
                'Accept' => 'application/json',
            ])->get($url, $queryParams);

            if (!$response->successful()) {
                Log::error('TextBee SMS polling failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return [];
            }

            $data = $response->json('data', []);

            if (empty($data)) {
                return [];
            }

            $messages = [];
            $latestReceivedAt = $lastPolledAt;

            foreach ($data as $sms) {
                $receivedAt = $sms['receivedAt'] ?? null;
                $messages[] = [
                    'sender' => $sms['sender'] ?? '',
                    'body' => $sms['message'] ?? '',
                    'receivedAt' => $receivedAt ?? '',
                ];

                if ($receivedAt && ($latestReceivedAt === null || $receivedAt > $latestReceivedAt)) {
                    $latestReceivedAt = $receivedAt;
                }
            }

            if ($latestReceivedAt) {
                Cache::put(self::CACHE_KEY, $latestReceivedAt);
            }

            return $messages;
        } catch (\Exception $e) {
            Log::error('TextBee SMS polling exception', [
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
