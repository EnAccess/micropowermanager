<?php

namespace App\Plugins\Whatsapp\Http\Controllers;

use App\Models\Sms;
use App\Plugins\Whatsapp\Models\WhatsappMessage;
use App\Plugins\Whatsapp\Services\WhatsappBotService;
use App\Plugins\Whatsapp\Services\WhatsappCredentialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

class WhatsappWebhookController extends Controller {
    public function __construct(
        private WhatsappCredentialService $credentialService,
        private WhatsappBotService $bot,
        private WhatsappMessage $message,
    ) {}

    /**
     * Meta's one-time handshake when the webhook is saved in the dashboard.
     * PHP turns "hub.mode" into "hub_mode", so both spellings are read.
     */
    public function verify(Request $request): Response {
        $mode = $request->query('hub_mode', $request->query('hub.mode'));
        $token = $request->query('hub_verify_token', $request->query('hub.verify_token'));
        $challenge = $request->query('hub_challenge', $request->query('hub.challenge'));

        $credentials = $this->credentialService->getCredentials();

        if ($mode === 'subscribe' && $credentials && $credentials->webhook_verify_token
            && hash_equals((string) $credentials->webhook_verify_token, (string) $token)) {
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    public function receive(Request $request): JsonResponse {
        $credentials = $this->credentialService->getCredentials();

        if (!$credentials || !$credentials->app_secret) {
            Log::warning('WhatsApp webhook rejected: app secret not configured');

            return response()->json(['error' => 'Webhook is not configured'], 403);
        }

        $expected = 'sha256='.hash_hmac('sha256', $request->getContent(), $credentials->app_secret);
        if (!hash_equals($expected, (string) $request->header('X-Hub-Signature-256'))) {
            Log::warning('WhatsApp webhook rejected: invalid signature');

            return response()->json(['error' => 'Invalid signature'], 403);
        }

        // always answer 200 for a valid signature, otherwise Meta keeps retrying
        try {
            foreach ((array) $request->input('entry', []) as $entry) {
                foreach ((array) ($entry['changes'] ?? []) as $change) {
                    $value = $change['value'] ?? [];

                    foreach ((array) ($value['statuses'] ?? []) as $status) {
                        $this->handleStatus($status);
                    }
                    foreach ((array) ($value['messages'] ?? []) as $message) {
                        $this->bot->handleInbound($message);
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::error('WhatsApp webhook processing failed', ['error' => $e->getMessage()]);
        }

        return response()->json(['status' => 'ok']);
    }

    /**
     * Delivery receipt for a message we sent: sent, delivered, read or failed.
     *
     * @param array<string, mixed> $status
     */
    private function handleStatus(array $status): void {
        $id = $status['id'] ?? null;
        $state = $status['status'] ?? null;
        $row = $id ? $this->message->newQuery()->where('message_id', $id)->first() : null;
        if (!$row || !$state) {
            return;
        }

        if ($state === 'failed') {
            $error = $status['errors'][0] ?? [];
            $text = trim(($error['code'] ?? '').' '.($error['title'] ?? $error['message'] ?? 'Delivery failed'));

            $row->update(['status' => 'failed', 'error' => $text]);
            Sms::query()->where('id', $row->sms_id)->update([
                'status' => Sms::STATUS_FAILED,
                'error_message' => 'WhatsApp: '.$text,
            ]);

            return;
        }

        // receipts can arrive out of order, so a status only ever moves forward
        $rank = ['sent' => 1, 'delivered' => 2, 'read' => 3];
        if (isset($rank[$state]) && $rank[$state] > ($rank[$row->status] ?? 0)) {
            $row->update(['status' => $state]);

            if ($state !== 'sent') {
                Sms::query()->where('id', $row->sms_id)->update(['status' => Sms::STATUS_DELIVERED]);
            }
        }
    }
}
