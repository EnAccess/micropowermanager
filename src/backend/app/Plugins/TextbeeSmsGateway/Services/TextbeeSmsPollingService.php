<?php

declare(strict_types=1);

namespace App\Plugins\TextbeeSmsGateway\Services;

use App\Plugins\TextbeeSmsGateway\Models\TextbeeCredential;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TextbeeSmsPollingService {
    private const BASE_URL = 'https://api.textbee.dev/api/v1';
    private const CACHE_KEY_RECEIVED_AFTER = 'textbee_last_polled_at';
    private const CACHE_KEY_RECENT_IDS = 'textbee_recent_message_ids';

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

        $lastPolledAt = Cache::get(self::CACHE_KEY_RECEIVED_AFTER);
        $recentIds = Cache::get(self::CACHE_KEY_RECENT_IDS, []);
        $recentIdsSet = array_flip($recentIds);

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
            $currentBatchIds = [];

            foreach ($data as $sms) {
                $id = $sms['_id'] ?? null;
                $receivedAt = $sms['receivedAt'] ?? null;

                if ($id !== null) {
                    $currentBatchIds[] = $id;

                    // TextBee's `receivedAfter` is inclusive, so messages at the
                    // boundary timestamp get re-returned. Skip anything we emitted
                    // in the previous poll.
                    if (isset($recentIdsSet[$id])) {
                        continue;
                    }
                }

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
                Cache::put(self::CACHE_KEY_RECEIVED_AFTER, $latestReceivedAt);
            }
            if ($currentBatchIds !== []) {
                Cache::put(self::CACHE_KEY_RECENT_IDS, $currentBatchIds);
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
